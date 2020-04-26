<?php

namespace StackPath\API;

use StackPath\Exception\API\InvalidHttpResponseException;
use StackPath\Exception\API\RequestException;
use StackPath\WordPress\Integration\WordPressException;
use StackPath\WordPress\Integration\WordPressInterface;
use StackPath\WordPress\Settings;
use WP_HTTP_Requests_Response;

/**
 * An interface to the StackPath API
 *
 * @see https://developer.wordpress.org/plugins/http-api/
 */
class Client
{
    const BASE_URL = 'https://gateway.stackpath.com';

    /**
     * WP HTTP request options to make on every API call
     *
     * @var array
     */
    const DEFAULT_REQUEST_OPTIONS = [
        'timeout' => 60,
        'httpversion' => '1.1',
        'user-agent' => 'StackPath WordPress Plugin/[VERSION] (+https://github.com/stackpath/stackpath-wordpress)',
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
    ];

    /**
     * These calls don't have a bearer token passed to them.
     *
     * @var array[]
     */
    const NO_AUTH_CALLS = [
        [
            'method' => 'POST',
            'path' => '/identity/v1/oauth2/token',
        ],
    ];

    /**
     * The StackPath plugin's settings
     *
     * These settings contain API authentication information.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * The WordPress instance to place HTTP requests through
     *
     * @var WordPressInterface
     */
    protected $wordPress;

    /**
     * The WordPress plugin's version for use in User-Agent strings
     *
     * @var string
     */
    protected $pluginVersion;

    /**
     * Build a StackPath API client
     *
     * @param Settings $settings
     * @param WordPressInterface $wordPress
     */
    public function __construct(Settings $settings, WordPressInterface $wordPress)
    {
        $this->settings = $settings;
        $this->wordPress = $wordPress;
    }

    /**
     * Set the plugin's version for use in User-Agent strings
     *
     * @param string $version
     */
    public function setPluginVersion($version)
    {
        $this->pluginVersion = $version;
    }

    /**
     * Authenticate to the StackPath API
     *
     * Save the new access token to WordPress if successful.
     *
     * @throws InvalidHttpResponseException
     * @throws RequestException
     * @throws WordPressException
     */
    public function authenticate()
    {
        $client = new self($this->settings, $this->wordPress);
        $client->setPluginVersion($this->pluginVersion);

        $result = $client->post('/identity/v1/oauth2/token', [
            'body' => json_encode([
                'client_id' => $this->settings->clientId,
                'client_secret' => $this->settings->clientSecret,
                'grant_type' => 'client_credentials',
            ]),
        ]);

        $this->settings->accessToken = new Token($result->decodedBody->access_token);
        $this->settings->save($this->wordPress);
    }

    /**
     * Make a call to the StackPath API
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return Response
     * @throws WordPressException when the request call results in a WordPress error
     * @throws InvalidHttpResponseException when the response doesn't have an 'http_response' key
     * @throws RequestException when an error was returned by the StackPath API
     */
    public function request($method, $url, array $options = [])
    {
        // Make sure the requested URL path begins with a "/".
        if (strpos($url, '/') !== 0) {
            $url = "/{$url}";
        }

        // Prepare the request
        $requestOptions = array_merge_recursive(self::DEFAULT_REQUEST_OPTIONS, $options);
        $requestOptions['method'] = $method;
        $requestOptions['user-agent'] = str_replace('[VERSION]', $this->pluginVersion, $requestOptions['user-agent']);

        // See if this call needs a bearer token
        $addAuthHeader = true;
        foreach (self::NO_AUTH_CALLS as $call) {
            if ($call['path'] === $url && $call['method'] === $requestOptions['method']) {
                $addAuthHeader = false;
                break;
            }
        }

        // See if the client needs to re-authenticate before making the call
        if ($addAuthHeader) {
            if ($this->settings->accessToken === null || $this->settings->accessToken->isExpired()) {
                $this->authenticate();
            }

            $requestOptions['headers']['Authorization'] = "Bearer {$this->settings->accessToken->accessToken}";
        }

        // Make the call
        /**
         * @var \WP_Error|array $response {
         *   @var WP_HTTP_Requests_Response http_response
         * }
         */
        $response = $this->wordPress->wpRemoteRequest(self::BASE_URL . $url, $requestOptions);

        // Check for errors in the call
        if ($this->wordPress->isWpError($response)) {
            throw new WordPressException($response);
        }

        if (
            !array_key_exists('http_response', $response)
            || !($response['http_response'] instanceof WP_HTTP_Requests_Response)
        ) {
            throw new InvalidHttpResponseException($response);
        }

        // Recast the response as a StackPath API response
        $response = Response::fromWordPressResponse($response['http_response']->get_response_object());
        $response->decodeBody();

        // If the call was a failure then throw the appropriate exception
        if (!$response->success) {
            throw RequestException::create($this->pluginVersion, $url, $requestOptions, $response);
        }

        // Finally, return the successful response
        return $response;
    }

    /**
     * Make a GET request of the StackPath API
     *
     * @param string $url
     * @param array $options
     * @return Response
     * @throws InvalidHttpResponseException
     * @throws RequestException
     * @throws WordPressException
     */
    public function get($url, array $options = [])
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * Make a POST request of the StackPath API
     *
     * @param string $url
     * @param array $options
     * @return Response
     * @throws InvalidHttpResponseException
     * @throws RequestException
     * @throws WordPressException
     */
    public function post($url, array $options = [])
    {
        return $this->request('POST', $url, $options);
    }

    /**
     * Make a PUT request of the StackPath API
     *
     * @param string $url
     * @param array $options
     * @return Response
     * @throws InvalidHttpResponseException
     * @throws RequestException
     * @throws WordPressException
     */
    public function put($url, array $options = [])
    {
        return $this->request('PATCH', $url, $options);
    }

    /**
     * Make a PATCH request of the StackPath API
     *
     * @param string $url
     * @param array $options
     * @return Response
     * @throws InvalidHttpResponseException
     * @throws RequestException
     * @throws WordPressException
     */
    public function patch($url, array $options = [])
    {
        return $this->request('PATCH', $url, $options);
    }

    /**
     * Make a DELETE request of the StackPath API
     *
     * @param string $url
     * @param array $options
     * @return Response
     * @throws InvalidHttpResponseException
     * @throws RequestException
     * @throws WordPressException
     */
    public function delete($url, array $options = [])
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * Make a HEAD request of the StackPath API
     *
     * @param string $url
     * @param array $options
     * @return Response
     * @throws InvalidHttpResponseException
     * @throws RequestException
     * @throws WordPressException
     */
    public function head($url, array $options = [])
    {
        return $this->request('HEAD', $url, $options);
    }
}
