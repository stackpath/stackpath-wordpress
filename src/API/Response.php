<?php

namespace StackPath\API;

use Requests_Response;

/**
 * Model a StackPath API call response
 *
 * Extend the WorPress Requests_Response with a JSON-decoded response body to
 * save work for the caller.
 */
class Response extends Requests_Response
{
    /**
     * Whether or not the response was a JSON response
     *
     * @var bool
     */
    public $jsonResponse = false;

    /**
     * The JSON decoded response body, if the response was a JSON response
     *
     * @var \stdClass
     */
    public $decodedBody;

    /**
     * Convert a WordPress response into a StackPath API response
     *
     * @param Requests_Response $response
     * @return Response
     */
    public static function fromWordPressResponse(Requests_Response $response)
    {
        // Here be dragons.
        //
        // Recast the response from a Request_Response to StackPath\API\Response
        // via serialization.
        return unserialize(
            preg_replace(
                '/^O:17:"Requests_Response"/',
                'O:' . strlen(__CLASS__) . ':"' . __CLASS__ . '"',
                serialize($response)
            )
        );
    }

    /**
     * Decode the JSON body in a response
     */
    public function decodeBody()
    {
        // If it's already been decoded then don't decode it again.
        if ($this->jsonResponse) {
            return;
        }

        $decoded = json_decode($this->body, false);

        if ($decoded !== null) {
            $this->jsonResponse = true;
            $this->decodedBody = $decoded;
            $this->body = '';
        }
    }
}
