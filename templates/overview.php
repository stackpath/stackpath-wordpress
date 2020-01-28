<?php

if (!defined('ABSPATH')) {
    die('!');
}

/** @var \StackPath\WordPress\Plugin $this */
?>

<h1>CDN Analytics</h1>

<div class="stackpath-dashboard-panel">
    <div id="stackpath-loading-edge-address">
        <div style="margin-top: 0">Loading Edge Address
            <img id="stackpath-loading-edge-address-loading" src="<?= $this->wordPress->adminUrl('images/loading.gif') ?>" alt="loading">
            <span id="stackpath-loading-edge-address-done" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/yes.png') ?>"></span>
            <span id="stackpath-loading-edge-address-error" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/no.png') ?>"></span>
            <span id="stackpath-loading-edge-address-error-message"></span>
        </div>

        <div>Validating Edge Address
            <img id="stackpath-validating-edge-address-loading" src="<?= $this->wordPress->adminUrl('images/loading.gif') ?>" alt="loading" style="display: none">
            <span id="stackpath-validating-edge-address-done" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/yes.png') ?>"></span>
            <span id="stackpath-validating-edge-address-error" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/no.png') ?>"></span>
            <span id="stackpath-validating-edge-address-error-message"></span>
        </div>
    </div>

    <div id="stackpath-loading-metrics">
        <div>Loading CDN request and bandwidth metrics
            <img id="stackpath-loading-bandwidth-metrics-loading" src="<?= $this->wordPress->adminUrl('images/loading.gif') ?>" alt="loading">
            <span id="stackpath-loading-bandwidth-metrics-done" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/yes.png') ?>"></span>
            <span id="stackpath-loading-bandwidth-metrics-error" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/no.png') ?>"></span>
            <span id="stackpath-loading-bandwidth-metrics-error-message"></span>
        </div>

        <div>Loading CDN location metrics
            <img id="stackpath-loading-location-metrics-loading" src="<?= $this->wordPress->adminUrl('images/loading.gif') ?>" alt="loading">
            <span id="stackpath-loading-location-metrics-done" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/yes.png') ?>"></span>
            <span id="stackpath-loading-location-metrics-error" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/no.png') ?>"></span>
            <span id="stackpath-loading-location-metrics-error-message"></span>
        </div>
    </div>

    <div id="stackpath-loading-errors" style="display: none; margin-bottom: 2em">
        <strong>We were unable to load all of your site's information. Please refresh this page to try again or contact <a href="https://control.stackpath.com/">StackPath customer support</a>.</strong>
    </div>

    <div id="stackpath-site-edge-address" style="display: none">
        <h2>Your site's Edge Address is <strong><span class="stackpath-edge-address-hostname"></span></strong></h2>

        <div id="stackpath-site-edge-address-valid" style="display: none">
            <p>StackPath's CDN edge network is caching and serving your site's content.</p>
        </div>

        <div id="stackpath-site-edge-address-invalid" style="display: none">
            <p>Your StackPath site is online but is not caching your WordPress site's content.</p>
            <p>Please contact your DNS provider to create a <strong>CNAME</strong> record pointing <strong><?= parse_url($this->wordPress->getSiteUrl())['host'] ?></strong> to <strong><span class="stackpath-edge-address-hostname"></span></strong>. See the <a href="https://support.stackpath.com/hc/en-us/articles/360001152903-How-to-use-the-StackPath-CDN-URL">StackPath Help Center</a> for more information.</p>
        </div>
    </div>

    <div id="stackpath-site-metrics" style="display: none">
        <h2>Bandwidth</h2>
        <div class="stackpath-line-chart">
            <canvas id="stackpath-site-bandwidth-chart"></canvas>
        </div>

        <h2>Requests</h2>
        <div class="stackpath-line-chart">
            <canvas id="stackpath-site-requests-chart"></canvas>
        </div>

        <div class="stackpath-doughnut-charts">
            <div class="stackpath-doughnut-chart-container">
                <h2>Cache Hits by Bandwidth</h2>
                <div class="stackpath-doughnut-chart">
                    <canvas id="stackpath-cache-hits-bandwidth-chart"></canvas>
                </div>
            </div>

            <div class="stackpath-doughnut-chart-container">
                <h2>Cache Hits by Request</h2>
                <div class="stackpath-doughnut-chart">
                    <canvas id="stackpath-cache-hits-requests-chart"></canvas>
                </div>
            </div>

            <div class="stackpath-doughnut-chart-container">
                <h2>Bandwidth by City</h2>
                <div class="stackpath-doughnut-chart">
                    <canvas id="stackpath-pop-bandwidth-chart"></canvas>
                </div>
            </div>

            <div class="stackpath-doughnut-chart-container">
                <h2>Requests by City</h2>
                <div class="stackpath-doughnut-chart">
                    <canvas id="stackpath-pop-requests-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($this->settings->site->hasFeature('waf')) : ?>
    <h1>WAF Analytics and Events</h1>

    <div class="stackpath-dashboard-panel">
        Coming soon!
    </div>
<?php endif; ?>
