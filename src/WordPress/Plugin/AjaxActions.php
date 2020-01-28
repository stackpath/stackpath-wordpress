<?php

namespace StackPath\WordPress\Plugin;

use DateTime;
use Exception;
use StackPath\Exception\Exception as StackPathException;
use StackPath\Exception\API\RequestException;
use StackPath\Site;
use StackPath\Stack;
use StackPath\WordPress\Message;
use StackPath\WordPress\Plugin;

/**
 * @property \StackPath\WordPress\Settings $settings
 * @property \StackPath\WordPress\Integration\WordPressInterface $wordPress
 */
trait AjaxActions
{
    public function findStacksAjaxAction()
    {
        $this->validateUserPermission(true);
        $this->validateAjaxNonce('_stackpath_find_stacks_nonce');

        try {
            $this->wordPress->wpDie(json_encode(Stack::findAll($this->apiClient)));
        } catch (Exception $e) {
            $this->wordPress->wpDie(
                json_encode(Message::fromException('Unable to load account stacks', $e)),
                $e->getCode() === 0 ? 500 : $e->getCode()
            );
        }
    }

    public function findSitesAjaxAction()
    {
        $this->validateUserPermission(true);
        $this->validateAjaxNonce('_stackpath_find_sites_nonce');

        if (!array_key_exists('stack_id', $_POST) || $_POST['stack_id'] === '') {
            $this->wordPress->wpDie(json_encode(new Message('No stack ID present')), 400);
        }

        try {
            $this->wordPress->wpDie(json_encode(Site::findAll((new Stack($_POST['stack_id'])), $this->apiClient)));
        } catch (Exception $e) {
            $this->wordPress->wpDie(
                json_encode(Message::fromException('Unable to load sites', $e)),
                $e->getCode() === 0 ? 500 : $e->getCode()
            );
        }
    }

    public function findSiteDeliveryDomainAjaxAction()
    {
        $this->validateUserPermission(true);
        $this->validateAjaxNonce('_stackpath_find_site_delivery_domains_nonce');

        if (!array_key_exists('stack_id', $_POST) || $_POST['stack_id'] === '') {
            $this->wordPress->wpDie(json_encode(new Message('No stack ID present')), 400);
        }

        if (!array_key_exists('site_id', $_POST) || $_POST['site_id'] === '') {
            $this->wordPress->wpDie(json_encode(new Message('No site ID present')), 400);
        }

        try {
            $this->wordPress->wpDie(json_encode(
                (new Site($_POST['site_id'], $_POST['stack_id']))->deliveryDomains($this->apiClient)
            ));
        } catch (Exception $e) {
            $this->wordPress->wpDie(
                json_encode(Message::fromException('Unable to load site delivery domains', $e)),
                $e->getCode() === 0 ? 500 : $e->getCode()
            );
        }
    }

    /**
     * Retrieve the configured site's Edge Address
     *
     * An Edge Address is the site's stackpathcdn.com domain name that all
     * traffic is delivered through.
     */
    public function loadEdgeAddressAjaxAction()
    {
        if ($this->settings->site === null) {
            $this->wordPress->wpDie(json_encode(new Message('A site must be configured to load an edge address')), 400);
        }

        if ($this->settings->stack === null) {
            $this->wordPress->wpDie(
                json_encode(new Message('A site and stack must be configured to load an edge address')),
                400
            );
        }

        try {
            $dnsTargets = $this->apiClient->get(
                "/cdn/v1/stacks/{$this->settings->stack->id}/sites/{$this->settings->site->id}/dns/targets"
            )->decodedBody->addresses;
        } catch (Exception $e) {
            $this->wordPress->wpDie(
                json_encode(Message::fromException('Unable to load site DNS targets', $e)),
                $e->getCode() === 0 ? 500 : $e->getCode()
            );
        }

        if (count($dnsTargets) === 0) {
            $this->wordPress->wpDie(
                json_encode(new Message('This site has no DNS targets. Please contact StackPath support.')),
                500
            );
        }

        $this->wordPress->wpDie(json_encode(['edgeAddress' => $dnsTargets[0]]));
    }

    /**
     * Verify the WordPress hostname is a CNAME target to the site's Edge Address
     */
    public function verifyEdgeAddressAjaxAction()
    {
        if (!isset($_POST['edgeAddress'])) {
            $this->wordPress->wpDie(json_encode(new Message('Cannot validate an empty Edge Address')), 400);
        }

        $edgeAddress = trim($_POST['edgeAddress']);

        if ($edgeAddress === '') {
            $this->wordPress->wpDie(json_encode(new Message('Cannot validate an empty Edge Address')), 400);
        }

        $lookupResult = dns_get_record(parse_url($this->wordPress->getSiteUrl())['host'], DNS_CNAME);

        if ($lookupResult === false) {
            $this->wordPress->wpDie(
                json_encode(new Message("Unable to query for the WordPress site's DNS CNAME target")),
                500
            );
        }

        // Make sure there was a CNAME lookup result
        if (count($lookupResult) === 0) {
            $this->wordPress->wpDie(json_encode(['valid' => false]));
        }

        // Make sure the CNAME lookup result was the right format
        if (!array_key_exists('target', $lookupResult[0])) {
            $this->wordPress->wpDie(json_encode(['valid' => false]));
        }

        // Make sure the CNAME lookup result matches the site's Edge Address
        $this->wordPress->wpDie(json_encode(['valid' => $lookupResult[0]['target'] === $edgeAddress]));
    }

    /**
     * Load the configured site's edge and origin metrics
     */
    public function loadSiteMetricsAjaxAction()
    {
        if ($this->settings->site === null) {
            $this->wordPress->wpDie(json_encode(new Message('A site must be configured to load metrics')), 400);
        }

        if ($this->settings->stack === null) {
            $this->wordPress->wpDie(
                json_encode(new Message('A site and stack must be configured to load metrics')),
                400
            );
        }

        $start = new DateTime('now - 30 days');
        $end = new DateTime();

        try {
            $this->wordPress->wpDie(json_encode($this->apiClient->get(
                "cdn/v1/stacks/{$this->settings->stack->id}/metrics?" . http_build_query([
                    'start_date' => $start->format(Plugin::DATETIME_FORMAT),
                    'end_date' => $end->format(Plugin::DATETIME_FORMAT),
                    'sites' => $this->settings->site->id,
                    'platforms' => 'CDO,CDE',
                    'granularity' => 'P1D',
                    'group_by' => 'PLATFORM',
                ])
            )->decodedBody->series));
        } catch (Exception $e) {
            $this->wordPress->wpDie(
                json_encode(Message::fromException('Unable to load site bandwidth metrics', $e)),
                $e->getCode() === 0 ? 500 : $e->getCode()
            );
        }
    }

    /**
     * Load the configured site's StackPath POP aggregated metrics
     */
    public function loadPopMetricsAjaxAction()
    {
        if ($this->settings->site === null) {
            $this->wordPress->wpDie(json_encode(new Message('A site must be configured to load metrics')), 400);
        }

        if ($this->settings->stack === null) {
            $this->wordPress->wpDie(
                json_encode(new Message('A site and stack must be configured to load metrics')),
                400
            );
        }

        $start = new DateTime('now - 30 days');
        $end = new DateTime();

        try {
            $this->wordPress->wpDie(json_encode($this->apiClient->get(
                "cdn/v1/stacks/{$this->settings->stack->id}/metrics?" . http_build_query([
                    'start_date' => $start->format(Plugin::DATETIME_FORMAT),
                    'end_date' => $end->format(Plugin::DATETIME_FORMAT),
                    'sites' => $this->settings->site->id,
                    'platforms' => 'CDO,CDE',
                    'granularity' => 'P1D',
                    'group_by' => 'POP',
                ])
            )->decodedBody->series));
        } catch (Exception $e) {
            $this->wordPress->wpDie(
                json_encode(Message::fromException('Unable to load site POP metrics', $e)),
                $e->getCode() === 0 ? 500 : $e->getCode()
            );
        }
    }
}
