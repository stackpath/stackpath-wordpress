<?php

namespace StackPath\WordPress\Integration;

use Exception;
use WP_Error;

/**
 * Wrap WordPress' WP_Error class as an exception
 */
class WordPressException extends Exception
{
    /**
     * The underlying WordPress error
     *
     * @var WP_Error
     */
    public $wordPressError;

    /**
     * Build a new WordPress exception
     *
     * @param \WP_Error $wordPressError
     */
    public function __construct(WP_Error $wordPressError)
    {
        $this->wordPressError = $wordPressError;
        parent::__construct();
    }
}
