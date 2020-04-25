<?php

namespace StackPath\WordPress;

use DateTime;
use Exception;
use StackPath\API\Client;
use StackPath\WordPress\Integration\WordPressInterface;
use StackPath\WordPress\Plugin\AjaxActions;
use StackPath\WordPress\Plugin\Pages;
use StackPath\WordPress\Plugin\PostActions;
use WP_Post;

class Plugin
{
    use AjaxActions;
    use Pages;
    use PostActions;

    /**
     * The name of this plugin in WordPress
     *
     * @var string
     */
    const NAME = 'stackpath';

    /**
     * The date-time format used by the StackPath API
     *
     * @var string
     */
    const DATETIME_FORMAT = 'Y-m-d\TH:i:s.u\Z';

    /**
     * The plugin's settings
     *
     * @var Settings
     */
    protected $settings;

    /**
     * How the plugin talks to the StackPath API
     *
     * @var Client
     */
    protected $apiClient;

    /**
     * How the plugin talks to the underlying WordPress instance
     *
     * @var WordPressInterface
     */
    protected $wordPress;

    /**
     * Notice messages to show to the user
     *
     * @var Message[]
     */
    protected $notices = [];

    /**
     * Error messages to show to the user
     *
     * @var Message[]
     */
    protected $errors = [];

    /**
     * Build a new StackPath WordPress plugin
     *
     * @param Settings $settings
     * @param Client $apiClient
     * @param WordPressInterface $wordPress
     */
    public function __construct(Settings $settings, Client $apiClient, WordPressInterface $wordPress)
    {
        $this->settings = $settings;
        $this->apiClient = $apiClient;
        $this->wordPress = $wordPress;

        $this->apiClient->setPluginVersion($this->version());
    }

    /**
     * Determine if a site is configured in the plugin
     *
     * The plugin loads different menu items and actions depending on whether a
     * site is configured or not.
     * @return bool
     */
    protected function isLinkedToSite()
    {
        return $this->settings->clientId !== null
            && $this->settings->clientSecret !== null
            && $this->settings->stack !== null
            && $this->settings->site !== null;
    }

    /**
     * Start the plugin
     *
     * Register all actions necessary to run the plugin with WordPress.
     */
    public function run()
    {
        // Load plugin settings
        $this->wordPress->addAction('admin_init', [$this, 'registerSettings']);

        // Register different admin menu items, POST actions, and AJAX actions
        // depending on if the plugin is linked to a StackPath site.
        $this->initializeLinked();
        $this->initializeUnlinked();

        // Load common JavaScript
        $this->wordPress->addAction('admin_enqueue_scripts', function () {
            $this->wordPress->wpEnqueueScript('stackpath', $this->jsUrl('stackpath.js'), ['jquery']);
            $this->wordPress->wpLocalizeScript('stackpath', '_stackpath_admin_url', $this->wordPress->adminUrl());
        });
    }

    /**
     * Load settings from Wordpress into the plugin
     */
    public function registerSettings()
    {
        $this->settings->register($this->wordPress);
    }

    /**
     * Register plugin features when the plugin is linked to a StackPath site
     */
    public function initializeLinked()
    {
        if (!$this->isLinkedToSite()) {
            return;
        }

        // Set admin menu items
        $this->wordPress->addAction('admin_menu', function () {
            $this->wordPress->addMenuPage(
                'StackPath',
                'StackPath',
                'manage_options',
                'stackpath',
                '',
                $this->menuIcon(),
                81
            );

            $this->wordPress->addSubmenuPage(
                'stackpath',
                'StackPath Site Overview',
                'Overview',
                'manage_options',
                'stackpath-overview',
                [$this, 'overviewPage']
            );

            $this->wordPress->addSubmenuPage(
                'stackpath',
                'StackPath Cache',
                'Cache',
                'manage_options',
                'stackpath-cache',
                [$this, 'cachePage']
            );

            $this->wordPress->addSubmenuPage(
                'stackpath',
                'StackPath Site Security',
                'Security',
                'manage_options',
                'stackpath-security',
                [$this, 'securityPage']
            );

            $this->wordPress->addSubmenuPage(
                'stackpath',
                'StackPath Settings',
                'Settings',
                'manage_options',
                'stackpath-settings',
                [$this, 'settingsPage']
            );

            // Here be dragons.
            //
            // Remove the duplicate "StackPath" item from the sub-menu.
            global $submenu;
            array_shift($submenu['stackpath']);
        });

        // Register actions
        $actions = [
            // POST
            'admin_post_stackpath_purge_everything' => 'purgeEverythingPostAction',
            'admin_post_stackpath_custom_purge' => 'customPurgePostAction',
            'admin_post_stackpath_auto_purge' => 'autoPurgeAction',
            'admin_post_stackpath_bypass_cache_on_wordpress_cookie' => 'bypassCacheOnWordPressCookieAction',
            'admin_post_stackpath_save_api_credentials' => 'saveApiCredentialsPostAction',
            'admin_post_stackpath_reset_plugin' => 'resetPluginPostAction',

            // AJAX
            'wp_ajax_stackpath_load_edge_address' => 'loadEdgeAddressAjaxAction',
            'wp_ajax_stackpath_verify_edge_address' => 'verifyEdgeAddressAjaxAction',
            'wp_ajax_stackpath_load_site_metrics' => 'loadSiteMetricsAjaxAction',
            'wp_ajax_stackpath_load_pop_metrics' => 'loadPopMetricsAjaxAction',
        ];

        foreach ($actions as $hook => $method) {
            $this->wordPress->addAction($hook, [$this, $method]);
        }

        // Auto-purge content on change if the user wants it.
        if ($this->settings->autoPurgeContent) {
            $this->wordPress->addAction('save_post_post', [$this, 'purgeUrl'], 10, 3);
            $this->wordPress->addAction('save_post_page', [$this, 'purgeUrl'], 10, 3);
        }
    }

    /**
     * Register plugin features when the plugin is not linked to a StackPath site
     */
    public function initializeUnlinked()
    {
        if ($this->isLinkedToSite()) {
            return;
        }

        // Set admin menu items
        $this->wordPress->addAction('admin_menu', function () {
            $this->wordPress->addMenuPage(
                'StackPath',
                'StackPath',
                'manage_options',
                'stackpath',
                [$this, 'unlinkedPage'],
                $this->menuIcon(),
                81
            );

            // The following pages aren't sub-menu pages, but are individual
            // pages linked from the main page.
            $this->wordPress->addSubmenuPage(
                null,
                'StackPath API Credentials',
                'API Credentials',
                'manage_options',
                'stackpath-log-in',
                [$this, 'logInPage']
            );

            $this->wordPress->addSubmenuPage(
                null,
                'Looking For Existing Service',
                'API Credentials',
                'manage_options',
                'stackpath-find-site',
                [$this, 'findSitePage']
            );
        });

        // Register actions
        $actions = [
            // POST
            'admin_post_stackpath_save_api_credentials' => 'saveApiCredentialsPostAction',
            'admin_post_stackpath_reset_plugin' => 'resetPluginPostAction',
            'admin_post_stackpath_attach_to_site' => 'attachToSitePostAction',
            'admin_post_stackpath_create_site' => 'createNewSitePostAction',

            // AJAX
            'wp_ajax_stackpath_find_stacks' => 'findStacksAjaxAction',
            'wp_ajax_stackpath_find_sites' => 'findSitesAjaxAction',
            'wp_ajax_stackpath_find_site_delivery_domains' => 'findSiteDeliveryDomainAjaxAction',
        ];

        foreach ($actions as $hook => $method) {
            $this->wordPress->addAction($hook, [$this, $method]);
        }
    }

    /**
     * Log a message to the error_log
     *
     * @param Message $message
     * @throws Exception
     */
    public function log(Message $message)
    {
        $now = new DateTime();
        $logEntry = "[{$now->format(DateTime::ATOM)}][StackPath WordPress Plugin {$this->version()}] {$message->title}";

        if ($message->hasDescription()) {
            $logEntry .= " - {$message->description}";
        }

        $this->wordPress->errorLog($logEntry);
    }

    /**
     * Get plugin metadata
     *
     * @return array
     */
    protected function pluginData()
    {
        return $this->wordPress->getPluginData(dirname(dirname(__DIR__)) . '/stackpath.php', false, false);
    }

    /**
     * Get the plugin's version
     *
     * @return string
     */
    protected function version()
    {
        $pluginData = $this->pluginData();

        return isset($pluginData['Version']) ? $pluginData['Version'] : '';
    }

    /**
     * Make sure the user has the right permission to access the plugin
     *
     * Present a JSON-encoded error message for invalid permissions to AJAX
     * requests. This terminates page execution if the permission isn't met,
     * and this should be run on every page and action.
     *
     * @param bool $ajax
     */
    protected function validateUserPermission($ajax = false)
    {
        if ($this->wordPress->currentUserCan('manage_options')) {
            return;
        }

        if ($ajax) {
            $this->wordPress->wpDie(
                json_encode(new Message('You must have the manage_options capability to use the StackPath plugin.')),
                403
            );
        }

        $this->wordPress->wpDie(
            'You must have the <i>manage_options</i> capability to use the StackPath plugin.',
            'Permission Denied',
            [
                'response' => 403,
                'back_link' => true,
            ]
        );
    }

    /**
     * Validate an action's nonce
     *
     * @see https://codex.wordpress.org/WordPress_Nonces
     * @param string $name
     * @param array $post
     * @param bool $isAjax
     * @return bool
     */
    protected function validNonce($name, array $post = [], $isAjax = false)
    {
        if (!isset($post[$name])) {
            return false;
        }

        if ($isAjax) {
            return $this->wordPress->checkAjaxReferer($name, $name, false);
        }

        if (!$this->wordPress->wpVerifyNonce($post[$name], $name)) {
            return false;
        }

        return true;
    }

    /**
     * Validate the nonce provided in an AJAX request
     *
     * This terminates the page action if the nonce is invalid, returning a 400
     * response to the user.
     *
     * @param string $name
     */
    protected function validateAjaxNonce($name)
    {
        if (!$this->wordPress->checkAjaxReferer($name, $name, false)) {
            $this->wordPress->wpDie(json_encode(Message::invalidAjaxNonce()), 400);
        }
    }

    /**
     * Redirect the browser to a given page
     *
     * Optionally provide transient data for use on the redirected page. This
     * function terminates page execution and should be called before anything
     * is rendered to the browser.
     *
     * @param string $page
     * @param TransientData|null $transientData
     */
    protected function redirectTo($page, TransientData $transientData = null)
    {
        if ($transientData !== null) {
            $transientData->save($this->wordPress);
        }

        $this->wordPress->wpRedirect($this->wordPress->adminUrl('admin.php') . "?page={$page}");
        exit;
    }

    /**
     * Display a page template
     *
     * Loading a page terminates page execution.
     *
     * @param string $name
     * @param array $pageData
     * @throws Exception
     */
    protected function loadPage($name, array $pageData = [])
    {
        // Load transient data from POST actions into page errors and notices.
        $transientData = TransientData::get($this->wordPress);
        $transientData->delete($this->wordPress);
        foreach ($transientData->notices as $notice) {
            $this->addNotice($notice);
        }

        foreach ($transientData->errors as $error) {
            $this->addError($error);
        }

        // Echo markup to the browser.
        //
        // Templates have the following variables available:
        // * $this - the plugin instance
        // * $transientData - transient data generated by POST actions
        // * $pageData - a key/value pair of data to load into the page
        // * Any global or static variables, functions, or classes
        $page = dirname(__DIR__, 2) . '/templates/layout/header.php';
        include $page;

        $page = dirname(__DIR__, 2) . "/templates/{$name}.php";
        include $page;

        $page = dirname(__DIR__, 2) . '/templates/layout/footer.php';
        include $page;
    }

    /**
     * Build a base64 encoded representation of the plugin's menu icon
     *
     * @see https://developer.wordpress.org/reference/functions/add_menu_page/
     * @return string
     */
    public function menuIcon()
    {
        return 'data:image/svg+xml;base64,'
            . base64_encode(
                file_get_contents("{$this->baseDir()}/assets/stackpath-monogram-standard-screen.svg")
            );
    }

    /**
     * Get the base plugin directory
     *
     * @return string
     */
    protected function baseDir()
    {
        return $this->wordPress->pluginDirPath(dirname(__DIR__));
    }

    /**
     * Get the base plugin URL
     *
     * @return string
     */
    protected function baseUrl()
    {
        return rtrim($this->wordPress->pluginDirUrl(dirname(__DIR__)), '/');
    }

    /**
     * Get the URL to a CSS file
     *
     * @param string|null $cssFile
     * @return string
     */
    protected function cssUrl($cssFile = null)
    {
        $baseUrl = "{$this->baseUrl()}/css";

        if ($cssFile === null) {
            return $baseUrl;
        }

        return "{$baseUrl}/{$cssFile}";
    }

    /**
     * Get the URL to a JavaScript file
     *
     * @param string|null $jsFile
     * @return string
     */
    protected function jsUrl($jsFile = null)
    {
        $baseUrl = "{$this->baseUrl()}/js";

        if ($jsFile === null) {
            return $baseUrl;
        }

        return "{$baseUrl}/{$jsFile}";
    }

    /**
     * Get an asset's URL
     *
     * @param string|null $asset
     * @return string
     */
    protected function assetUrl($asset = null)
    {
        $baseUrl = "{$this->baseUrl()}/assets";

        if ($asset === null) {
            return $baseUrl;
        }

        return "{$baseUrl}/{$asset}";
    }

    /**
     * Add a notice to display to the user
     *
     * @param Message $message
     */
    protected function addNotice(Message $message)
    {
        $this->notices [] = $message;
    }

    /**
     * Log and add an error to display to the user
     *
     * @param Message $message
     * @throws Exception
     */
    protected function addError(Message $message)
    {
        $this->errors [] = $message;
        $this->log($message);
    }

    /**
     * Determine if there are errors or notices to display to the user
     *
     * @return bool
     */
    protected function hasMessages()
    {
        return count($this->notices) > 0 || count($this->errors) > 0;
    }

    /**
     * Purge a single post
     *
     * This function is triggered by the WordPress save_post_post and
     * save_post_page actions.
     *
     * @see https://developer.wordpress.org/reference/hooks/save_post_post-post_type/
     * @param int $postId
     * @param WP_Post $post
     * @param bool $update
     * @throws Exception
     */
    public function purgeUrl($postId, WP_Post $post, $update)
    {
        if (!$this->settings->hasValidApiSettings()) {
            return;
        }

        if ($this->settings->stack === null || $this->settings->site === null) {
            return;
        }

        $url = $this->wordPress->getPermalink($post);

        try {
            $this->apiClient->post("cdn/v1/stacks/{$this->settings->stack->id}/purge", [
                'body' => json_encode([
                    'items' => [
                        [
                            'url' => $url,
                            'recursive' => true,
                            'invalidateOnly' => false,
                        ],
                    ],
                ]),
            ])->decodedBody->id;
        } catch (Exception $e) {
            $this->addError(Message::fromException("Unable to purge URL {$url}", $e));
        }
    }
}
