<?php

namespace StackPath\WordPress;

use Exception;
use StackPath\Exception\API\RequestException;

/**
 * A message to display to a user
 *
 * Typically this is rendered as a notice or error message in the plugin UI.
 */
class Message
{
    /**
     * The message's title
     *
     * @var string
     */
    public $title;

    /**
     * The message's description
     *
     * @var string|null
     */
    public $description;

    /**
     * A set of key => any-type pairs of debug information
     *
     * This is typically used to show low level details when the plugin
     * encounters an error from the StackPath API.
     *
     * If WP_DEBUG and WP_DEBUG_DISPLAY are true, and the error message didn't
     * come from an AJAX action, then debug information is shown to the user in
     * the UI. If WP_DEBUG and WB_DEBUG_LOG are both true then debug information
     * is sent to the PHP error_log.
     *
     * @var array
     */
    public $debugInformation = [];

    /**
     * Build a new user message
     *
     * @param string $title
     * @param string|null $description
     * @param array $debugInformation
     */
    public function __construct($title, $description = null, array $debugInformation = [])
    {
        $this->title = $title;
        $this->description = $description;
        $this->debugInformation = $debugInformation;
    }

    /**
     * Build a message from a title and exception
     *
     * @param string $title
     * @param Exception $e
     * @return Message
     */
    public static function fromException($title, Exception $e)
    {
        $description = $e->getMessage();
        $debugInformation = [];

        // API request exceptions have more information to show to users.
        if ($e instanceof RequestException) {
            $description = $e->detailedErrorMessage();
            $debugInformation = ['Request URL' => $e->requestUrl];

            if ($e->response->requestId !== null) {
                $debugInformation['Request ID'] = $e->response->requestId;
            }

            $debugInformation['Request options'] = $e->requestOptions;
            $debugInformation['Response'] = $e->response;
        }

        return new self($title, $description, $debugInformation);
    }

    /**
     * Format debug information for display or logging
     *
     * Debug information is in PHP's print_r() format with blank lines removed
     * and two space indentation for easier readability.
     *
     * @param mixed $input
     * @return string
     */
    public static function debugFormat($input)
    {
        return preg_replace('/^\h*\v+/m', '', preg_replace('/ {2}/', ' ', print_r($input, true)));
    }

    /**
     * Factory an invalid POST form nonce message
     *
     * @return Message
     */
    public static function invalidFormNonce()
    {
        return new self('Invalid form nonce', 'Please refresh this page and try again');
    }

    /**
     * Factory an invalid AJAX nonce message
     *
     * @return Message
     */
    public static function invalidAjaxNonce()
    {
        return new self('Invalid AJAX nonce');
    }

    /**
     * Determine if the message has a description or not
     *
     * @return bool
     */
    public function hasDescription()
    {
        return $this->description !== null;
    }

    /**
     * Determine if the message has debug information
     *
     * @return bool
     */
    public function hasDebugInformation()
    {
        return count($this->debugInformation) > 0;
    }
}
