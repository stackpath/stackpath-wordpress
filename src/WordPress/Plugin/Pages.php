<?php

namespace StackPath\WordPress\Plugin;

/**
 * @property \StackPath\WordPress\Settings $settings
 * @property \StackPath\WordPress\Integration\WordPressInterface $wordPress
 */
trait Pages
{
    public function unlinkedPage()
    {
        $this->validateUserPermission();

        // If the user has API settings but is on the main unlinked page then
        // they've previously configured API settings but never linked the
        // plugin to a site, so send them to the find site page.
        if ($this->settings->hasValidApiSettings()) {
            $this->findSitePage();
            return;
        }

        $this->loadPage('unlinked');
    }

    public function findSitePage()
    {
        $this->validateUserPermission();

        // If the user is on the find site page, but doesn't have an API access
        // token then they manually loaded this page. Display the main unlinked
        // page to get them started.
        if (!$this->settings->hasValidApiSettings()) {
            $this->unlinkedPage();
            return;
        }

        $siteHost = parse_url($this->wordPress->getSiteUrl(), PHP_URL_HOST);
        $this->wordPress->wpEnqueueScript('stackpath-find-site', $this->jsUrl('find-site.js'), ['stackpath']);
        $this->wordPress->wpLocalizeScript('stackpath-find-site', '_stackpath_site_host', $siteHost);

        $nonces = [
            '_stackpath_find_stacks_nonce',
            '_stackpath_find_sites_nonce',
            '_stackpath_find_site_delivery_domains_nonce',
        ];

        foreach ($nonces as $nonce) {
            $this->wordPress->wpLocalizeScript('stackpath-find-site', $nonce, $this->wordPress->wpCreateNonce($nonce));
        }

        $this->loadPage('find-site', ['siteHost' => $siteHost]);
    }

    public function logInPage()
    {
        $this->validateUserPermission();
        $this->loadPage('log-in');
    }

    public function overviewPage()
    {
        $this->validateUserPermission();

        $this->wordPress->wpEnqueueScript('stackpath-overview', $this->jsUrl('overview.js'), ['stackpath']);
        $this->wordPress->wpEnqueueScript(
            'stackpath-charts',
            $this->jsUrl('Chart.bundle.min.js'),
            ['stackpath-overview']
        );

        $nonces = [
            '_stackpath_load_edge_rules_nonce',
            '_stackpath_load_edge_address_nonce',
            '_stackpath_verify_edge_address_nonce',
            '_stackpath_load_site_metrics_nonce',
            '_stackpath_load_pop_metrics_nonce',
        ];

        foreach ($nonces as $nonce) {
            $this->wordPress->wpLocalizeScript('stackpath-overview', $nonce, $this->wordPress->wpCreateNonce($nonce));
        }

        $this->loadPage('overview');
    }

    public function cachePage()
    {
        $this->validateUserPermission();
        $this->loadPage('cache');
    }

    public function securityPage()
    {
        $this->validateUserPermission();
        $this->loadPage('security');
    }

    public function settingsPage()
    {
        $this->validateUserPermission();
        $this->loadPage('settings');
    }
}
