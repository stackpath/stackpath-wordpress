<?php

namespace StackPath\API;

use DateTime;
use DateTimeZone;

/**
 * Model the OAuth2 bearer token provided by the StackPath API
 */
class Token
{
    /**
     * The bearer token's full value
     *
     * Use this value in the Authorization header of StackPath API calls
     *
     * @var string|null
     */
    public $accessToken;

    /**
     * The token's JWT jti claim
     *
     * @var string
     */
    public $id;

    /**
     * The token's JWT audience claim
     *
     * @var string
     */
    public $audience;

    /**
     * The date the token expires on
     *
     * @var DateTime|null
     */
    public $expiresOn;

    /**
     * The date the token was issued
     *
     * @var DateTime|null
     */
    public $issuedAt;

    /**
     * The token's JWT issuer claim
     *
     * @var string
     */
    public $issuer;

    /**
     * The token's JWT subject claim
     *
     * @var string
     */
    public $subject;

    /**
     * The id of the StackPath the token is issued to
     *
     * @var string
     */
    public $userId;

    /**
     * The ids of the StackPath accounts the token's user has access to
     *
     * @var string[]
     */
    public $accountIds = [];

    /**
     * @var string
     */
    public $emailAddress;

    /**
     * Build a new token
     *
     * @param string $accessToken
     * @throws \Exception when the issued at and expires on dates can't hydrate into DateTime objects
     */
    public function __construct($accessToken)
    {
        $this->accessToken = $accessToken;

        // Decode the JWT into the rest of the token's fields
        $payload = explode('.', $this->accessToken)[1];
        $decoded = json_decode(base64_decode($payload), true);

        $this->id = array_key_exists('https://identity.stackpath.com/jti', $decoded)
            ? $decoded['https://identity.stackpath.com/jti']
            : null;
        $this->audience = array_key_exists('aud', $decoded)
            ? $decoded['aud']
            : null;
        $this->expiresOn = array_key_exists('exp', $decoded)
            ? new DateTime("@{$decoded['exp']}", new DateTimeZone('UTC'))
            : null;
        $this->issuedAt = array_key_exists('iat', $decoded)
            ? new DateTime("@{$decoded['iat']}", new DateTimeZone('UTC'))
            : null;
        $this->issuer = array_key_exists('iss', $decoded)
            ? $decoded['iss']
            : null;
        $this->subject = array_key_exists('sub', $decoded)
            ? $decoded['sub']
            : null;
        $this->emailAddress = array_key_exists('https://identity.stackpath.com/email', $decoded)
            ? $decoded['https://identity.stackpath.com/email']
            : null;

        if ($decoded['sub'] !== null && strpos($decoded['sub'], '|') !== false) {
            $this->userId = explode('|', $decoded['sub'])[1];
        }

        $this->accountIds = [];

        if (array_key_exists('https://identity.stackpath.com/accounts', $decoded)) {
            foreach ($decoded['https://identity.stackpath.com/accounts'] as $account) {
                $this->accountIds[] = $account['id'];
            }
        }
    }

    /**
     * Determine if a bearer token is expired or not.
     *
     * @return bool
     * @throws \Exception if the stored token has invalid date formats
     */
    public function isExpired()
    {
        return new DateTime('now', new DateTimeZone('UTC')) > $this->expiresOn;
    }
}
