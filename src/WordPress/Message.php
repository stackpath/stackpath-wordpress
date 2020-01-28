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
     * Build a new user message
     *
     * @param string $title
     * @param string|null $description
     */
    public function __construct($title, $description = null)
    {
        $this->title = $title;
        $this->description = $description;
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
        $description = $e instanceof RequestException ? $e->detailedErrorMessage() : $e->getMessage();
        return new self($title, $description);
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
}
