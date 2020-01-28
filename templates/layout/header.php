<?php

if (!defined('ABSPATH')) {
    die('!');
}

/** @var \StackPath\WordPress\Plugin $this */
?>

<link rel="stylesheet" href="<?= $this->cssUrl('style.css') ?>" type="text/css"/>

<div id="stackpath_page_header">
    <a id="stackpath_header_logo" href="https://www.stackpath.com/" title="StackPath, LLC" style="background-image: url(<?= $this->assetUrl('stackpath-logo-standard-screen.svg') ?>)">StackPath</a>
</div>

<div id="screen-meta" class="metabox-prefs">
    <div id="stackpath-more-info-wrap" class="hidden" tabindex="-1" aria-label="StackPath More Info">
        <div id="stackpath-more-info-columns">
            <div id="stackpath-more-info-main">
                <p><strong>Thank you for using the StackPath WordPress plugin!</strong></p>
                <p>Check out this plugin on <a href="https://www.github.com/stackpath/stackpath-wordpress/">GitHub</a> if you'd like to give feedback or see how it works.</p>
                <?php if (WP_DEBUG) : ?>
                    <p><strong>Debug information</strong></p>
                    <p>You are seeing this because both <code>WP_DEBUG</code> is set to <code>true</code> in <code>wp-config.php</code>.</p>
                    <div class="stackpath-dashboard-panel" style="padding: 1em; margin: 0 0 12px;">
                        <?php global $wp_version, $wpdb; ?>

                        <div><strong>General Information</strong></div>
                        <p style="margin-top: 0">
                            PHP version: <?=PHP_VERSION?><br>
                            MySQL version: <?=$wpdb->db_version()?><br>
                            WordPress version: <?=$wp_version?><br>
                            StackPath plugin version: <?=$this->version() ?>
                        </p>

                        <div><strong>Installed Plugins</strong></div>
                        <p style="margin-top: 0">
                            <?php foreach ($this->wordPress->getPlugins() as $plugin) : ?>
                                <a href="<?=$this->wordPress->escUrl($plugin['PluginURI'])?>"><?=$this->wordPress->escHtml($plugin['Name'])?></a>: <?=$this->wordPress->escHtml($plugin['Version'])?><br>
                            <?php endforeach; ?>
                        </p>

                        <div><strong>StackPath Plugin Configuration</strong></div>
                        <p style="margin-top: 0">
                            API client ID: <?=$this->settings->clientId === null ? '<i>null</i>' : $this->wordPress->escHtml($this->settings->clientId)?><br>
                            API client secret: <?=$this->settings->clientId === null ? '<i>null</i>' : '<i>REDACTED</i>'?><br>
                            API access token defined: <?=$this->settings->accessToken === null ? 'No' : 'Yes'?><br>
                            Stack ID: <?=$this->settings->stack === null ? '<i>null</i>' : $this->wordPress->escHtml($this->settings->stack->id)?><br>
                            Site ID: <?=$this->settings->site === null ? '<i>null</i>' : $this->wordPress->escHtml($this->settings->site->id)?><br>
                            Auto-purge new and changed content: <?=$this->settings->autoPurgeContent === null ? '<i>null</i>' : ($this->settings->autoPurgeContent ? 'Yes' : 'No') ?><br>
                            Bypass cache on WordPress cookies: <?=$this->settings->bypassCacheOnWordPressCookies === null ? '<i>null</i>' : ($this->settings->bypassCacheOnWordPressCookies ? 'Yes' : 'No') ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="stackpath-more-info-sidebar">
                <p><strong>Further information:</strong></p>
                <p><a href="https://www.stackpath.com/">StackPath</a></p>
                <p><a href="https://control.stackpath.com/">StackPath Control Panel</a></p>
                <p><a href="https://support.stackpath.com/hc/en-us/">StackPath Help Center</a></p>
                <p><a href="https://stackpath.dev/">StackPath Developer Resources</a></p>
            </div>
        </div>
    </div>
</div>

<div id="screen-meta-links">
    <div id="stackpath-more-info-link-wrap" class="hide-if-no-js screen-meta-toggle">
        <button type="button" id="stackpath-more-info-link" class="button show-settings" aria-controls="stackpath-more-info-wrap" aria-expanded="false">More</button>
    </div>
</div>

<div class="wrap">
    <?php if ($this->hasMessages()) : ?>
        <h1 class="stackpath-message-separator"></h1>
    <?php endif; ?>

    <?php
    $messageType = 'error';
    foreach ($this->errors as $message) {
        include dirname(__DIR__) . '/partials/message.php';
    }

    $messageType = 'notice';
    foreach ($this->notices as $message) {
        include dirname(__DIR__) . '/partials/message.php';
    }

    unset($messageType);
    ?>
