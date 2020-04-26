<?php

namespace StackPath\WordPress\Plugin;

use Exception;
use StackPath\API\Client;
use StackPath\Exception\API\AuthenticationException;
use StackPath\Site;
use StackPath\Stack;
use StackPath\WordPress\Message;
use StackPath\WordPress\Settings;
use StackPath\WordPress\TransientData;

/**
 * @property \StackPath\WordPress\Settings $settings
 * @property \StackPath\API\Client $apiClient
 * @property \StackPath\WordPress\Integration\WordPressInterface $wordPress
 */
trait PostActions
{
    public function saveApiCredentialsPostAction()
    {
        $this->validateUserPermission();

        $transientData = new TransientData();
        $transientData->addFormData('client_id', $_POST['client_id']);
        $transientData->addFormData('client_secret', $_POST['client_secret']);

        if (!$this->validNonce('stackpath_save_api_credentials_nonce', $_POST)) {
            $transientData->addError(Message::invalidFormNonce());
            $this->redirectTo($_POST['redirect_to_on_error'], $transientData);
        }

        try {
            // Try to authenticate to StackPath with the client ID and secret
            $this->settings->clientId = $_POST['client_id'];
            $this->settings->clientSecret = $_POST['client_secret'];
            $this->apiClient->authenticate();
        } catch (AuthenticationException $e) {
            // Note authentication errors
            $transientData->addError(new Message(
                'Unable to authenticate with the StackPath API',
                'Please check your client ID and secret and try again.',
                [
                    'Request URL' => $e->requestUrl,
                    'Request ID' => $e->response->requestId,
                    'Request options' => $e->requestOptions,
                    'Response' => $e->response,
                ]
            ));

            $this->redirectTo('stackpath-log-in', $transientData);
        } catch (Exception $e) {
            // Note API connectivity errors
            $transientData->addError(Message::fromException(
                'There was an error authenticating to the StackPath API',
                $e
            ));

            $this->redirectTo($_POST['redirect_to_on_error'], $transientData);
        }

        $transientData->addNotice(new Message('StackPath API credentials verified'));
        $this->redirectTo($_POST['redirect_to_on_success']);
    }

    public function attachToSitePostAction()
    {
        $this->validateUserPermission();

        $transientData = new TransientData();
        $transientData->addFormData('stack_id_site_id', $_POST['stack_id_site_id']);

        if (!$this->validNonce('stackpath_attach_to_site_nonce', $_POST)) {
            $transientData->addError(Message::invalidFormNonce());
            $this->redirectTo('stackpath-find-site', $transientData);
        }

        // The attach to site form uses a single field with the stack and site
        // IDs separated by an underscore. Make sure both were provided and
        // aren't blank before saving.
        if (strpos($_POST['stack_id_site_id'], '_') === false) {
            $transientData->addError(new Message('Invalid site selected'));
            $this->redirectTo('stackpath-find-site', $transientData);
        }

        list ($stackId, $siteId) = explode('_', $_POST['stack_id_site_id'], 2);
        $stackId = trim($stackId);
        $siteId = trim($siteId);

        if ($stackId === '' || $siteId === '') {
            $transientData->addError(new Message('Invalid site selected'));
            $this->redirectTo('stackpath-find-site', $transientData);
        }

        // Retrieve the stack and site to make sure they're valid.
        try {
            $stack = Stack::find($stackId, $this->apiClient);
        } catch (Exception $e) {
            $transientData->addError(new Message(
                'Invalid site selected',
                'The selected site contained an invalid stack ID.'
            ));
            $this->redirectTo('stackpath-find-site', $transientData);
        }

        try {
            $site = Site::find($stackId, $siteId, $this->apiClient);
        } catch (Exception $e) {
            $transientData->addError(new Message(
                'Invalid site selected',
                'The selected site contained an invalid site ID.'
            ));
            $this->redirectTo('stackpath-find-site', $transientData);
        }

        // Save the stack and site to the database.
        $this->settings->stack = $stack;
        $this->settings->site = $site;
        $this->settings->save($this->wordPress);

        $transientData->addNotice(new Message(
            'Success!',
            'Your WordPress site is now linked to the StackPath edge network.'
        ));
        $this->redirectTo('stackpath-overview', $transientData);
    }

    public function createNewSitePostAction()
    {

    }

    public function purgeEverythingPostAction()
    {
        $this->validateUserPermission();
        $transientData = new TransientData();

        if (!$this->validNonce('stackpath_purge_everything_nonce', $_POST)) {
            $transientData->addError(Message::invalidFormNonce());
            $this->redirectTo('stackpath-cache', $transientData);
        }

        try {
            $this->settings->site->purgeAll($this->apiClient);
            $transientData->addNotice(new Message('CDN cache purge initiated'));
        } catch (Exception $e) {
            $transientData->addError(Message::fromException('Unable to purge the CDN cache', $e));
        }

        $this->redirectTo('stackpath-cache', $transientData);
    }

    public function customPurgePostAction()
    {
        $this->validateUserPermission();
        $transientData = new TransientData();
        $transientData->addFormData('paths', $_POST['paths']);

        if (!$this->validNonce('stackpath_custom_purge_nonce', $_POST)) {
            $transientData->addError(Message::invalidFormNonce());
            $this->redirectTo('stackpath-cache', $transientData);
        }

        try {
            $this->settings->site->purgePaths(explode("\n", $_POST['paths']), $this->apiClient);
            $transientData->addNotice(new Message('CDN cache purge initiated'));
        } catch (Exception $e) {
            $transientData->addError(Message::fromException('Unable to purge the CDN cache', $e));
        }

        $this->redirectTo('stackpath-cache', $transientData);
    }

    public function autoPurgeAction()
    {
        $this->validateUserPermission();
        $transientData = new TransientData();
        $autoPurge = false;

        if (array_key_exists('auto_purge', $_POST) && $_POST['auto_purge'] === 'on') {
            $autoPurge = true;
        }

        if (!$this->validNonce('stackpath_auto_purge_nonce', $_POST)) {
            $transientData->addFormData('auto_purge', $autoPurge);
            $transientData->addError(Message::invalidFormNonce());
            $this->redirectTo('stackpath-cache', $transientData);
        }

        $this->settings->autoPurgeContent = $autoPurge;
        $this->settings->save($this->wordPress);
        $transientData->addNotice(new Message('Auto purge setting updated'));
        $this->redirectTo('stackpath-cache', $transientData);
    }

    public function bypassCacheOnWordPressCookieAction()
    {
        $this->validateUserPermission();
        $transientData = new TransientData();
        $bypassCache = false;

        if (array_key_exists('bypass_cache', $_POST) && $_POST['bypass_cache'] === 'on') {
            $bypassCache = true;
        }

        if (!$this->validNonce('stackpath_bypass_cache_on_wordpress_cookie_nonce', $_POST)) {
            $transientData->addFormData('bypass_cache', $bypassCache);
            $transientData->addError(Message::invalidFormNonce());
            $this->redirectTo('stackpath-cache', $transientData);
        }

        try {
            if ($bypassCache) {
                $this->settings->site->enableBypassCacheOnWordPressCookies($this->apiClient);
                $this->settings->bypassCacheOnWordPressCookies = true;
                $transientData->addNotice(new Message('Bypass cache for WordPress cookies enabled'));
            } else {
                $this->settings->site->disableBypassCacheOnWordPressCookies($this->apiClient);
                $this->settings->bypassCacheOnWordPressCookies = false;
                $transientData->addNotice(new Message('Bypass cache for WordPress cookies disabled'));
            }
        } catch (Exception $e) {
            $message = $bypassCache
                ? 'Unable to set bypass cache at StackPath'
                : 'Unable to unset bypass cache at StackPath';
            $transientData->addError(Message::fromException($message, $e));
        }

        $this->settings->save($this->wordPress);
        $this->redirectTo('stackpath-cache', $transientData);
    }

    public function resetPluginPostAction()
    {
        $this->validateUserPermission();
        $transientData = new TransientData();

        if (!$this->validNonce('stackpath_reset_plugin_nonce', $_POST)) {
            $transientData->addError(Message::invalidFormNonce());
            $this->redirectTo($_POST['redirect_to_on_error'], $transientData);
        }

        $this->settings = new Settings();
        $this->settings->save($this->wordPress);
        $transientData->addNotice(new Message('Plugin settings reset'));
        $this->redirectTo($_POST['redirect_to_on_success'], $transientData);
    }
}
