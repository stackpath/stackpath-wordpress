<?php

namespace StackPath\Exception\API;

use Requests_Response;
use StackPath\API\Response;
use StackPath\Exception\Exception;
use StackPath\WordPress\Message;

/**
 * StackPath backend API request exception
 *
 * Request exceptions are thrown when a call made by a StackPath\API\Client
 * receives an HTTP 4xx or 5xx error. Decode error messages in the response body
 * and populate them in an exception object for easier error checking by code
 * that makes StackPath API requests.
 */
class RequestException extends Exception
{
    /**
     * The GRPC status code associated with a StackPath API error.
     *
     * @see https://github.com/grpc/grpc/blob/master/doc/statuscodes.md
     * @var int|null
     */
    public $grpcCode;

    /**
     * The error message received by the StackPath API.
     *
     * @var string
     */
    public $message;

    /**
     * Error details received by the StackPath API.
     *
     * @var array
     */
    public $details = [];

    /**
     * The URL of the call that resulted in an error
     *
     * @var string
     */
    public $requestUrl;

    /**
     * The options of the request that resulted in an error
     *
     * @var array
     */
    public $requestOptions = [];

    /**
     * The response that resulted in an error
     *
     * @var Requests_Response
     */
    public $response;

    /**
     * Build a new StackPath backend exception.
     *
     * @param int|null $grpcCode
     * @param string $message
     * @param array $details
     * @param string $requestUrl
     * @param array $requestOptions
     * @param Requests_Response $response
     */
    public function __construct(
        $grpcCode,
        $message,
        array $details,
        $requestUrl,
        array $requestOptions,
        Requests_Response $response
    ) {
        $this->grpcCode = $grpcCode;
        $this->message = $message;
        $this->details = $details;
        $this->requestUrl = $requestUrl;
        $this->requestOptions = $requestOptions;
        $this->response = $response;

        parent::__construct();
    }

    /**
     * Factory a new StackPath API exception.
     *
     * @param string $requestUrl
     * @param array $requestOptions
     * @param Response $response
     * @return RequestException
     */
    public static function create(
        $requestUrl,
        array $requestOptions,
        Response $response
    ) {
        // Sanitize passwords and authentication tokens out of the request
        // options.
        if (array_key_exists('body', $requestOptions)) {
            $requestOptions['body'] = preg_replace(
                '/"client_secret":"[A-Za-z0-9]*"/',
                '"client_secret":"REDACTED"',
                $requestOptions['body']
            );
        }

        if (
            array_key_exists('headers', $requestOptions)
            && array_key_exists('Authorization', $requestOptions['headers'])
        ) {
            $requestOptions['headers']['Authorization'] = 'Bearer REDACTED';
        }

        // Log the error if debugging is set.
        if (
            defined('WP_DEBUG')
            && defined('WP_DEBUG_LOG')
            && WP_DEBUG
            && WP_DEBUG_LOG
        ) {
            error_log('An error was received from the StackPath API');
            error_log("Request URL: {$requestUrl}");
            error_log('Request options:');
            error_log(Message::debugFormat($requestOptions));
            error_log('Response:');
            error_log(Message::debugFormat($response));
        }

        // Pull the error messages out of the response body
        $code = null;
        $message = null;
        $details = [];

        // The StackPath API returns JSON-encoded errors, but in case this call
        // didn't the set the exception message to the response body.
        if ($response->jsonResponse) {
            $code = property_exists($response->decodedBody, 'code') ? $response->decodedBody->code : null;
            $message = property_exists($response->decodedBody, 'message') ? $response->decodedBody->message : null ;
            $details = property_exists($response->decodedBody, 'details') ? $response->decodedBody->details : [];
        } else {
            $message = $response->body;
        }

        // Check for an authentication error first
        //
        // If the requesting client ID is unknown then StackPath returns a 404
        // response to the authentication call.
        if (
            $requestUrl === '/identity/v1/oauth2/token'
            && $requestOptions['method'] === 'POST'
            && in_array($response->status_code, [401, 404], true)
        ) {
            return new AuthenticationException(
                $code,
                $message,
                $details,
                $requestUrl,
                $requestOptions,
                $response
            );
        }

        // Factory the returned exception into a client-side or server-side
        // exception.
        $level = (int) floor($response->status_code / 100);

        switch ($level) {
            case 4:
                return new ClientException($code, $message, $details, $requestUrl, $requestOptions, $response);

            case 5:
                return new ServerException($code, $message, $details, $requestUrl, $requestOptions, $response);

            default:
                return new self($code, $message, $details, $requestUrl, $requestOptions, $response);
        }
    }

    /**
     * Represent a RequestException's message and details in a single message
     *
     * @return string
     */
    public function detailedErrorMessage()
    {
        $description = $this->message;

        if (count($this->details) > 0) {
            if ($description !== '') {
                $description .= "\n";
            }

            $description .= implode("\n", $this->details);
        }

        return $description;
    }
}
