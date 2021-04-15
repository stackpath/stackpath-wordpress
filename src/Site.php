<?php

namespace StackPath;

use DateTime;
use DateTimeZone;
use StackPath\API\Client;
use StackPath\Exception\Exception;
use StackPath\WordPress\Plugin;
use stdClass;

/**
 * Model an individual site with StackPath service
 */
class Site
{
    /**
     * The site's unique identifier
     *
     * @var string
     */
    public $id;

    /**
     * The id of the stack that the site belongs to
     *
     * @var string
     */
    public $stackId;

    /**
     * The site's name as it appears in the Stackpath control panel
     *
     * This is typically the domain name of the site.
     *
     * @var string
     */
    public $label;

    /**
     * The site's status
     *
     * Delivery will only work when the site is in "ACTIVE" status.
     *
     * @var string
     */
    public $status;

    /**
     * The features attached to a site
     *
     * Available features may include CDN caching, WAF protection, and
     * server-less scripting support.
     *
     * @var string[]
     */
    public $features = [];

    /**
     * The date a site was created
     *
     * @var DateTime
     */
    public $createdAt;

    /**
     * The date a site was last updated
     *
     * @var DateTime
     */
    public $updatedAt;

    /**
     * URLs used as API URLs for sites with the WAF feature
     *
     * The StackPath WAF processes these URLs differently than standard browser
     * requests.
     *
     * @var string[]
     */
    public $apiUrls = [];

    /**
     * Whether or not a site's WAF feature is in monitor mode
     *
     * The StackPath WAF will log but permit requests that are normally blocked
     * when the site is in monitor mode.
     *
     * @var bool
     */
    public $monitoring;

    /**
     * Build a site object
     *
     * @param string $id
     * @param string $stackId
     * @param string|null $label
     * @param string|null $status
     * @param string[] $features
     * @param string[] $apiUrls
     * @param bool $monitoring
     * @param DateTime|null $createdAt
     * @param DateTime|null $updatedAt
     */
    public function __construct(
        $id,
        $stackId,
        $label = null,
        $status = null,
        array $features = [],
        array $apiUrls = [],
        $monitoring = false,
        DateTime $createdAt = null,
        DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->stackId = $stackId;
        $this->label = $label;
        $this->status = $status;
        $this->features = $features;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->apiUrls = $apiUrls;
        $this->monitoring = $monitoring;
    }

    /**
     * Build a site object from a decoded StackPath API result
     *
     * @param stdClass $site
     * @return Site
     * @throws \Exception if the created or updated dates don't translate into DateTime objects
     */
    protected static function fromResult(stdClass $site)
    {
        return new Site(
            $site->id,
            $site->stackId,
            $site->label,
            $site->status,
            $site->features,
            $site->apiUrls,
            $site->monitoring,
            DateTime::createFromFormat(Plugin::DATETIME_FORMAT, $site->createdAt, new DateTimeZone('UTC')),
            DateTime::createFromFormat(Plugin::DATETIME_FORMAT, $site->updatedAt, new DateTimeZone('UTC'))
        );
    }

    /**
     * Retrieve all active sites in a stack
     *
     * @param Stack $stack
     * @param Client $stackPath
     * @return Site[]
     * @throws \Exception
     */
    public static function findAll(Stack $stack, Client $stackPath)
    {
        $sites = [];
        $query = [
            'page_request.filter' => 'status="ACTIVE"',
            'page_request.first' => 100,
        ];

        while (true) {
            $response = $stackPath->get(
                "/delivery/v1/stacks/{$stack->id}/sites?" . http_build_query($query)
            );

            foreach ($response->decodedBody->results as $site) {
                $sites[] = static::fromResult($site);
            }

            if (!$response->decodedBody->pageInfo->hasNextPage) {
                break;
            }

            $query['page_request.after'] = $response->decodedBody->pageInfo->endCursor;
        }

        usort($sites, static function (Site $a, Site $b) {
            return strcmp($a->label, $b->label);
        });

        return $sites;
    }

    /**
     * Find a site by id in the given stack
     *
     * @param string $stackId
     * @param string $siteId
     * @param Client $stackPath
     * @return Site
     * @throws \Exception
     */
    public static function find($stackId, $siteId, Client $stackPath)
    {
        return self::fromResult(
            $stackPath->get("/delivery/v1/stacks/{$stackId}/sites/{$siteId}")->decodedBody->site
        );
    }

    /**
     * Retrieve a site's DNS targets
     *
     * DNS targets are CNAME records that must exist for site traffic to be
     * delivered to the StackPath CDN.
     *
     * @param Client $stackPath
     * @return string[]
     * @throws \Exception
     */
    public function dnsTargets(Client $stackPath)
    {
        return $stackPath->get(
            "/cdn/v1/stacks/{$this->stackId}/sites/{$this->id}/dns/targets"
        )->decodedBody->addresses;
    }

    /**
     * Retrieve a site's delivery domains
     *
     * Delivery domains are the domain names that the StackPath CDN listens on
     * for site requests.
     *
     * @param Client $stackPath
     * @return string[]
     * @throws Exception\API\InvalidHttpResponseException
     * @throws Exception\API\RequestException
     * @throws WordPress\Integration\WordPressException
     */
    public function deliveryDomains(Client $stackPath)
    {
        $deliveryDomains = [];
        $query = ['page_request.first' => 100,];

        while (true) {
            $response = $stackPath->get(
                "/delivery/v1/stacks/{$this->stackId}/sites/{$this->id}/delivery-domains?" . http_build_query($query)
            );

            foreach ($response->decodedBody->results as $deliveryDomain) {
                $deliveryDomains[] = $deliveryDomain->domain;
            }

            if (!$response->decodedBody->pageInfo->hasNextPage) {
                break;
            }

            $query['page_request.after'] = $response->decodedBody->pageInfo->endCursor;
        }

        usort($deliveryDomains, static function ($a, $b) {
            return strcmp($a, $b);
        });

        return $deliveryDomains;
    }

    /**
     * Purge all of the site's content from the StackPath CDN
     *
     * Return a purge key that can be used to check the purge request's status.
     *
     * @param Client $stackPath
     * @return string
     * @throws \Exception
     */
    public function purgeAll(Client $stackPath)
    {
        $dnsTargets = $this->dnsTargets($stackPath);

        return $stackPath->post("cdn/v1/stacks/{$this->stackId}/purge", [
            'body' => json_encode([
                'items' => [
                    [
                        'url' => "//{$dnsTargets[0]}/",
                        'recursive' => true,
                        'invalidateOnly' => false,
                    ],
                ],
            ]),
        ])->decodedBody->id;
    }

    /**
     * Purge certain site paths from the StackPath CDN
     *
     * Return a purge key that can be used to check the purge request's status.
     *
     * @param string[] $paths
     * @param Client $stackPath
     * @return string
     * @throws \Exception
     */
    public function purgePaths(array $paths, Client $stackPath)
    {
        $dnsTargets = $this->dnsTargets($stackPath);

        if (empty($paths)) {
            throw new Exception('Please provide paths to purge');
        }

        $items = array_map(function ($path) use ($dnsTargets) {
            $path = trim($path);

            if ($path === '') {
                throw new Exception('Unable to purge an empty path');
            }

            if (substr($path, 0, 1) !== '/') {
                $path = "/{$path}";
            }

            return [
                'url' => "//{$dnsTargets[0]}{$path}",
                'recursive' => true,
                'invalidateOnly' => false,
            ];
        }, $paths);

        return $stackPath->post("cdn/v1/stacks/{$this->stackId}/purge", [
            'body' => json_encode(['items' => $items]),
        ])->decodedBody->id;
    }

    /**
     * Find a site's CDS scope
     *
     * The CDS scope contains the site's CDN configuration and Edge Rules
     *
     * @param Client $stackPath
     * @return stdClass
     * @throws Exception
     * @throws Exception\API\InvalidHttpResponseException
     * @throws Exception\API\RequestException
     * @throws WordPress\Integration\WordPressException
     * @todo handle pagination
     */
    protected function cdsScope(Client $stackPath)
    {
        $scopes = $stackPath->get(
            "/cdn/v1/stacks/{$this->stackId}/sites/{$this->id}/scopes"
        )->decodedBody->results;

        foreach ($scopes as $scope) {
            if ($scope->platform === 'CDS') {
                return $scope;
            }
        }

        throw new Exception("Unable to find the site's CDS scope");
    }

    /**
     * Retrieve a site's Edge Rules
     *
     * @param Client $stackPath
     * @return stdClass[]
     * @throws Exception
     * @throws Exception\API\InvalidHttpResponseException
     * @throws Exception\API\RequestException
     * @throws WordPress\Integration\WordPressException
     */
    public function edgeRules(Client $stackPath)
    {
        return $stackPath->get(
            "/cdn/v1/stacks/{$this->stackId}/sites/{$this->id}/scopes/{$this->cdsScope($stackPath)->id}/rules"
        )->decodedBody->results;
    }

    /**
     * Enable a site's bypassCache config for WordPress cookies
     *
     * @param Client $stackPath
     * @return stdClass
     * @throws Exception
     * @throws Exception\API\InvalidHttpResponseException
     * @throws Exception\API\RequestException
     * @throws WordPress\Integration\WordPressException
     */
    public function enableBypassCacheOnWordPressCookies(Client $stackPath)
    {
        return $stackPath->patch(
            "/cdn/v1/stacks/{$this->stackId}/sites/{$this->id}/scopes/{$this->cdsScope($stackPath)->id}/configuration",
            [
                'body' => json_encode([
                    'configuration' => [
                        'bypassCache' => [
                            [
                                'enabled' => true,
                                'cookieFilter' => 'wp-*,wordpress_*,comment_*,woocommerce_*',
                            ],
                        ],
                    ],
                ]),
            ]
        )->decodedBody->configuration->bypassCache;
    }

    /**
     * Disable a site's bypassCache config for WordPress cookies
     *
     * @param Client $stackPath
     * @return stdClass
     * @throws Exception
     * @throws Exception\API\InvalidHttpResponseException
     * @throws Exception\API\RequestException
     * @throws WordPress\Integration\WordPressException
     */
    public function disableBypassCacheOnWordPressCookies(Client $stackPath)
    {
        return $stackPath->patch(
            "/cdn/v1/stacks/{$this->stackId}/sites/{$this->id}/scopes/{$this->cdsScope($stackPath)->id}/configuration",
            [
                'body' => json_encode([
                    'configuration' => [
                        'bypassCache' => [
                            [
                                'enabled' => false,
                                'cookieFilter' => 'wp-*,wordpress_*,comment_*,woocommerce_*',
                            ],
                        ],
                    ],
                ]),
            ]
        )->decodedBody->configuration->bypassCache;
    }

    /**
     * Determine if a site has the given feature like CDN or WAF
     *
     * @param string $feature
     * @return bool
     */
    public function hasFeature($feature)
    {
        return in_array(strtoupper($feature), $this->features);
    }
}
