<?php

if (!defined('ABSPATH')) {
    die('!');
}

/** @var \StackPath\WordPress\Plugin $this */
/** @var array $pageData */
/** @var \StackPath\WordPress\TransientData $transientData */
?>

<h1>Searching your StackPath account for <?=$pageData['siteHost']?></h1>

<div class="stackpath-dashboard-panel" style="padding-bottom: 23px">
    Loading account stacks
    <img id="stackpath-loading-account-stacks-loading" src="<?= $this->wordPress->adminUrl('images/loading.gif') ?>" alt="loading">
    <span id="stackpath-loading-account-stacks-error" style="display: none"><img src="<?= $this->wordPress->adminUrl('images/no.png') ?>" alt="error"></span>
    <span id="stackpath-loading-account-stacks-error-message"></span>

    <div id="stackpath-loading-account-sites"></div>

    <div id="stackpath-site-found" style="display: none; margin-top: 1em">
        <strong>We found "<?=$pageData['siteHost']?>" on your StackPath account! You can attach this WordPress site to it or create a new StackPath site.</strong>
    </div>

    <div id="stackpath-site-not-found" style="display: none; margin-top: 1em">
        <strong>We couldn't find "<?=$pageData['siteHost']?>" on your StackPath account. You can attach this WordPress site to one of your existing StackPath sites or create a new one.</strong>
    </div>

    <div id="stackpath-site-search-error-message" style="display: none; margin-top: 1em">
        <strong>Unable to search for your StackPath site. Please refresh this page and try again.</strong>
    </div>
</div>

<div id="stackpath-attach-to-existing-site" style="display: none">
    <h2>Attach to an exiting site</h2>

    <div class="stackpath-dashboard-panel">
        <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post">
            <input type="hidden" name="action" value="stackpath_attach_to_site">
            <input type="hidden" name="stackpath_attach_to_site_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_attach_to_site_nonce') ?>">

            <div class="stackpath-column-flex-form">
                <div class="stackpath-column-flex-stack-setting">
                    <label for="stackpath-existing-site-id">Select a site</label>
                </div>
                <div class="stackpath-column-flex-stack-setting">
                    <select id="stackpath-existing-site-id" name="stack_id_site_id" required>
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 20px">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Attach to Site">
            </div>
        </form>
    </div>
</div>

<div id="stackpath-create-new-site" style="display: none">
    <h2>Create new StackPath service</h2>

    <div class="stackpath-dashboard-panel">
        <p style="margin-top: 0">Creating a new StackPath site may result in additional charges to your StackPath account.</p>

        <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post">
            <input type="hidden" name="action" value="stackpath_create_site">
            <input type="hidden" name="stackpath_create_site_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_create_site_nonce') ?>">

            <div class="stackpath-column-flex-form">
                <div class="stackpath-column-flex-stack-setting">
                    <label for="stackpath-stack-id">Select a stack</label>
                </div>

                <div class="stackpath-column-flex-stack-setting">
                    <select id="stackpath-stack-id" name="stack_id" required>
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <div class="stackpath-column-flex-form" style="margin-top: 6px">
                <div class="stackpath-column-flex-stack-setting">
                    <label for="stackpath-new-stack-name">Or create a new stack</label>
                </div>

                <div class="stackpath-column-flex-stack-setting">
                    <input type="text" id="stackpath-new-stack-name" name="stack_name" placeholder="New stack name" value="<?= $this->wordPress->escAttr($transientData->getFormData('stack_name')) ?>">
                </div>
            </div>

            <div class="stackpath-column-flex-form" style="margin-top: 6px">
                <div class="stackpath-column-flex-stack-setting">Features</div>

                <div>
                    <label>
                        <input type="checkbox" name="feature_cdn" checked disabled> CDN
                    </label>
                    &nbsp;
                    <label>
                        <input type="checkbox" name="feature_waf" disabled> WAF (Coming soon!)
                    </label>
                </div>
            </div>

            <div style="margin-top: 20px">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Create New Site">
            </div>
        </form>
    </div>
</div>

<h1>Start Over</h1>

<div class="stackpath-dashboard-panel">
    <div>Reset the plugin to re-enter your login settings and start over.</div>

    <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post">
        <input type="hidden" name="action" value="stackpath_reset_plugin">
        <input type="hidden" name="redirect_to_on_success" value="stackpath">
        <input type="hidden" name="redirect_to_on_error" value="stackpath-find-site">
        <input type="hidden" name="stackpath_reset_plugin_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_reset_plugin_nonce') ?>">

        <div style="margin-top: 20px">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Start Over">
        </div>
    </form>
</div>
