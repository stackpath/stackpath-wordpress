<?php

namespace StackPath\Exception\API;

use StackPath\Exception\Exception;

/**
 * When a WordPress HTTP call returns a response with invalid information
 */
class InvalidHttpResponseException extends Exception
{
    /**
     * The underlying wp_remote_request() result
     *
     * @var array
     */
    public $response = [];

    /**
     * Build a new invalid HTTP response
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
        parent::__construct('A WordPress HTTP request resulted in an unparseable response');
    }
}
