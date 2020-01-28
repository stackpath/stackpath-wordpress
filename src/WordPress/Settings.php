<?php

namespace StackPath\WordPress;

use StackPath\API\Token;
use StackPath\Exception\Exception;
use StackPath\Site;
use StackPath\Stack;
use StackPath\WordPress\Integration\WordPressInterface;

/**
 * The StackPath WordPress plugin's settings
 *
 * StackPath should be considered the source of truth for the CDN service's
 * configuration, but store enough information in this object to bootstrap the
 * plugin and point to where the CDN service is at StackPath.
 *
 * Settings are stored in the stackpath_options value in the wp_options table as
 * a serialized array.
 */
class Settings
{
    /**
     * The name of the plugin setting's option_id in the wp_options table
     *
     * @var string
     */
    const WP_OPTIONS_NAME = 'stackpath_options';

    /**
     * The user's StackPath API OAuth client id
     *
     * @var string|null
     */
    public $clientId;

    /**
     * The user's StackPath API OAuth client secret
     *
     * @var string|null
     */
    public $clientSecret;

    /**
     * The stack that StackPath service is provisioned on
     *
     * @var \StackPath\Stack|null
     */
    public $stack;

    /**
     * The StackPath site in front of the WordPress installation
     *
     * @var \StackPath\Site|null
     */
    public $site;

    /**
     * An authenticated Authorization bearer token for the StackPath API
     *
     * This is automatically refreshed by the API client and stored in the
     * database to avoid unnecessary authentication API calls.
     *
     * @var Token|null
     */
    public $accessToken;

    /**
     * Whether or not the StackPath plugin should automatically purge CDN
     * content on local content changes
     *
     * @var bool|null
     */
    public $autoPurgeContent;

    /**
     * @var bool|null
     */
    public $bypassCacheOnWordPressCookies;

    /**
     * Build new plugin settings
     *
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @param Stack|null $stack
     * @param Site|null $site
     * @param bool|null $autoPurgeContent
     * @param bool|null $bypassCacheOnWordPressCookies
     * @param Token|null $accessToken
     */
    public function __construct(
        $clientId = null,
        $clientSecret = null,
        Stack $stack = null,
        Site $site = null,
        Token $accessToken = null,
        $autoPurgeContent = null,
        $bypassCacheOnWordPressCookies = null
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->stack = $stack;
        $this->site = $site;
        $this->accessToken = $accessToken;
        $this->autoPurgeContent = $autoPurgeContent;
        $this->bypassCacheOnWordPressCookies = $bypassCacheOnWordPressCookies;
    }

    /**
     * Build a new Settings object from its associated WordPress options
     *
     * @see https://developer.wordpress.org/reference/functions/get_option/
     * @throws Exception when $options isn't an array or false
     * @param array|bool $options
     * @return Settings
     */
    public static function fromWordPressOptions($options = false)
    {
        if ($options === false || $options === '') {
            return new Settings();
        }

        if (!is_array($options)) {
            throw new Exception('An unknown structure was received when querying for StackPath plugin options');
        }

        return new Settings(
            array_key_exists('client_id', $options) && $options['client_id'] !== ''
                ? $options['client_id']
                : null,
            array_key_exists('client_secret', $options) && $options['client_secret'] !== ''
                ? $options['client_secret']
                : null,
            array_key_exists('stack', $options) && $options['stack'] instanceof Stack
                ? $options['stack']
                : null,
            array_key_exists('site', $options) && $options['site'] instanceof Site
                ? $options['site']
                : null,
            array_key_exists('access_token', $options) && $options['access_token'] instanceof Token
                ? $options['access_token']
                : null,
            array_key_exists('auto_purge', $options) && $options['auto_purge'] !== ''
                ? $options['auto_purge']
                : null,
            array_key_exists('bypass_cache_on_wordpress_cookies', $options)
            && $options['bypass_cache_on_wordpress_cookies'] !== ''
                ? $options['bypass_cache_on_wordpress_cookies']
                : null
        );
    }

    /**
     * Determine if the client ID and secret are valid
     *
     * These API settings are considered valid if the settings object has a
     * bearer token populated, which could only happen with valid API
     * credentials.
     *
     * @return bool
     */
    public function hasValidApiSettings()
    {
        return $this->accessToken !== null;
    }

    public function register(WordPressInterface $wordPress)
    {
        $noOp = static function () {
        };

        $wordPress->registerSetting(Plugin::NAME, self::WP_OPTIONS_NAME);
        $wordPress->addSettingsField('client_id', 'Client ID', $noOp, Plugin::NAME);
        $wordPress->addSettingsField('client_secret', 'Client Secret', $noOp, Plugin::NAME);
        $wordPress->addSettingsField('stack', 'Stack', $noOp, Plugin::NAME);
        $wordPress->addSettingsField('site', 'Site', $noOp, Plugin::NAME);
        $wordPress->addSettingsField('access_token', 'Bearer Token', $noOp, Plugin::NAME);
        $wordPress->addSettingsField('auto_purge', 'Auto Purge Content on Change', $noOp, Plugin::NAME);
        $wordPress->addSettingsField(
            'bypass_cache_on_wordpress_cookies',
            'Bypass Cache on WordPress Cookies',
            $noOp,
            Plugin::NAME
        );
    }

    /**
     * Save settings to WordPress
     *
     * @param WordPressInterface $wordPress
     */
    public function save(WordPressInterface $wordPress)
    {
        $wordPress->updateOption(self::WP_OPTIONS_NAME, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'stack' => $this->stack,
            'site' => $this->site,
            'access_token' => $this->accessToken,
            'auto_purge' => $this->autoPurgeContent,
            'bypass_cache_on_wordpress_cookies' => $this->bypassCacheOnWordPressCookies,
        ]);
    }
}
