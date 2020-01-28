<?php

namespace StackPath;

use DateTime;
use DateTimeZone;
use StackPath\API\Client;
use StackPath\WordPress\Plugin;
use stdClass;

/**
 * A folder-like container that contain's the plugin's CDN service
 *
 * @see https://support.stackpath.com/hc/en-us/articles/360001067983-Getting-Started-with-Stacks
 */
class Stack
{
    /**
     * A stack's unique identifier
     *
     * @var string
     */
    public $id;

    /**
     * The id of the account that owns the stack
     *
     * @var string|null
     */
    public $accountId;

    /**
     * A stack's slug
     *
     * Slugs are used as identifiers by many StackPath services
     *
     * @var string|null
     */
    public $slug;

    /**
     * A stack's name
     *
     * @var string|null
     */
    public $name;

    /**
     * A stack's status
     *
     * StackPath services may only be provisioned on ACTIVE stacks.
     *
     * @var string|null
     */
    public $status;

    /**
     * The date a stack was created
     *
     * @var DateTime|null
     */
    public $createdAt;

    /**
     * The date a stack was last updated
     *
     * @var DateTime|null
     */
    public $updatedAt;

    /**
     * Build a new stack
     *
     * @param string $id
     * @param string|null $accountId
     * @param string|null $slug
     * @param string|null $name
     * @param string|null $status
     * @param DateTime|null $createdAt
     * @param DateTime|null $updatedAt
     */
    public function __construct(
        $id,
        $accountId = null,
        $slug = null,
        $name = null,
        $status = null,
        DateTime $createdAt = null,
        DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->accountId = $accountId;
        $this->slug = $slug;
        $this->name = $name;
        $this->status = $status;

        if ($createdAt !== null) {
            $this->createdAt = $createdAt;
        }

        if ($updatedAt !== null) {
            $this->updatedAt = $updatedAt;
        }
    }

    /**
     * Build a stack object from a decoded StackPath API result
     *
     * @param stdClass $stack
     * @return Stack
     * @throws \Exception if the created or updated dates don't translate into DateTime objects
     */
    protected static function fromResult(stdClass $stack)
    {
        return new Stack(
            $stack->id,
            $stack->accountId,
            $stack->slug,
            $stack->name,
            $stack->status,
            DateTime::createFromFormat(Plugin::DATETIME_FORMAT, $stack->createdAt, new DateTimeZone('UTC')),
            DateTime::createFromFormat(Plugin::DATETIME_FORMAT, $stack->updatedAt, new DateTimeZone('UTC'))
        );
    }

    /**
     * Retrieve a stack by id
     *
     * @param string $id
     * @param Client $stackPath
     * @return Stack
     * @throws Exception\API\InvalidHttpResponseException
     * @throws Exception\API\RequestException
     * @throws WordPress\Integration\WordPressException
     */
    public static function find($id, Client $stackPath)
    {
        return self::fromResult($stackPath->get("/stack/v1/stacks/{$id}")->decodedBody);
    }
    /**
     * Retrieve all stacks from StackPath
     *
     * @param Client $stackPath
     * @return Stack[]
     * @throws \Exception
     */
    public static function findAll(Client $stackPath)
    {
        $stacks = [];
        $query = [
            'page_request.filter' => 'status="ACTIVE"',
            'page_request.first' => 100,
        ];

        while (true) {
            $response = $stackPath->get('/stack/v1/stacks?' . http_build_query($query));

            foreach ($response->decodedBody->results as $stack) {
                $stacks[] = static::fromResult($stack);
            }

            if (!$response->decodedBody->pageInfo->hasNextPage) {
                break;
            }

            $query['page_request.after'] = $response->decodedBody->pageInfo->endCursor;
        }

        usort($stacks, static function (Stack $a, Stack $b) {
            return strcmp($a->name, $b->name);
        });

        return $stacks;
    }

    /**
     * Create a new stack at StackPath
     *
     * @param string $name
     * @param string $accountId
     * @param Client $stackPath
     * @return Stack
     * @throws \Exception
     */
    public static function create($name, $accountId, Client $stackPath)
    {
        $response = $stackPath->post('/stack/v1/stacks', [
            'body' => json_encode([
                'name' => $name,
                'account_id' => $accountId,
            ]),
        ]);

        return self::fromResult($response->decodedBody->stack);
    }
}
