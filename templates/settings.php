<?php

if (!defined('ABSPATH')) {
    die('!');
}

/** @var \StackPath\WordPress\Plugin $this */
/** @var \StackPath\WordPress\TransientData $transientData */

// Figure out if the client id and secret should be pre-populated on the form.
// Prioritize values from transient data in case the form is being re-submitted,
// otherwise use saved plugin settings if they exist.
$clientId = null;
$clientSecret = null;

if ($transientData->getFormData('client_id') !== null) {
    $clientId = $transientData->getFormData('client_id');
} elseif ($this->settings->clientId !== null) {
    $clientId = $this->settings->clientId;
}

if ($transientData->getFormData('client_secret') !== null) {
    $clientSecret = $transientData->getFormData('client_secret');
} elseif ($this->settings->clientId !== null) {
    $clientSecret = $this->settings->clientSecret;
}

?>

<h1>StackPath API Credentials</h1>

<div class="stackpath-dashboard-panel">
    <div>Your StackPath API credentials generate the access tokens that this plugin uses to communicate with the <a href="https://stackpath.dev/reference" target="_blank">StackPath API</a>.</div>

    <p style="margin-bottom: 20px">You will need to reset and re-configure this plugin if you enter API credentials that belong to a user on another account or a user that does not have access to the configured StackPath site.</p>

    <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post">
        <input type="hidden" name="action" value="stackpath_save_api_credentials">
        <input type="hidden" name="redirect_to_on_success" value="stackpath-settings">
        <input type="hidden" name="redirect_to_on_error" value="stackpath-settings">
        <input type="hidden" name="stackpath_save_api_credentials_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_save_api_credentials_nonce') ?>">

        <div class="stackpath-column-flex-form" style="margin-bottom: 1em">
            <div class="stackpath-column-flex-stack-setting">
                <label for="stackpath-client-id">Client ID</label>
            </div>

            <div class="stackpath-column-flex-stack-setting">
                <input type="text" id="stackpath-client-id" name="client_id" value="<?= $this->wordPress->escAttr($clientId) ?>" class="regular-text" required><br>
                <p style="margin-top: 0">Your client ID is found on the <a href="https://control.stackpath.com/api-management" target="_blank">API management page</a> in the StackPath customer portal.</p>
            </div>
        </div>

        <div class="stackpath-column-flex-form">
            <div class="stackpath-column-flex-stack-setting">
                <label for="stackpath-client-secret">Client Secret</label>
            </div>

            <div class="stackpath-column-flex-stack-setting">
                <input type="password" id="stackpath-client-secret" name="client_secret" value="<?= $this->wordPress->escAttr($clientSecret) ?>" class="regular-text" required><br>
                <p style="margin: 0">Client secrets are generated with the client ID but are only shown once in the StackPath customer portal due to their sensitive nature.</p>
            </div>
        </div>

        <div style="margin-top: 20px">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Credentials">
        </div>
    </form>
</div>

<h1>Reset</h1>

<div class="stackpath-dashboard-panel">
    <div>Resetting the plugin erases all settings. The plugin will need to be set up again when complete.</div>

    <p style="margin-bottom: 20px">This does not change any settings or services at StackPath. Please log into the <a href="https://control.stackpath.com/" target="_blank">StackPath customer portal</a> to modify or cancel service.</p>

    <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post" onsubmit="return confirm('Are you sure you want to reset the StackPath plugin?');">
        <input type="hidden" name="action" value="stackpath_reset_plugin">
        <input type="hidden" name="redirect_to_on_success" value="stackpath">
        <input type="hidden" name="redirect_to_on_error" value="stackpath-settings">
        <input type="hidden" name="stackpath_reset_plugin_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_reset_plugin_nonce') ?>">

        <div style="margin-top: 20px">
            <input type="submit" name="submit" id="submit" class="button button-primary" style="background: #ba0000; border-color: #ba0000" value="Reset Plugin">
        </div>
    </form>
</div>
