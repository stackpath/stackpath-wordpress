<?php

namespace StackPath\WordPress\Integration;

/**
 * A interface to WordPress functionality
 *
 * Bridge WordPress and modern development practices with an interface layer
 * that allows for local WordPress development as well as mocking WordPress
 * functionality for testing.
 *
 * This interface mostly matches WordPress functionality. Differences include:
 * * Method and parameter names conform to the PSR-2 code style.
 * * Functions that echo information to display instead return the echo'd
 *   contents.
 *
 * This isn't an exhaustive list of all internal WordPress functions, but the
 * ones needed for the StackPath plugin.
 */
interface WordPressInterface
{
    // Load
    public function isWpError($thing);

    // Functions
    public function wpDie($message = '', $title = '', $args = []);
    public function getFileData($file, array $defaultHeaders, $context = '');

    // Pluggable
    public function wpCreateNonce($action = -1);
    public function wpVerifyNonce($nonce, $action = -1);
    public function wpRedirect($location, $status = 302, $xRedirectBy = 'WordPress');
    public function checkAjaxReferer($action = -1, $queryArg = false, $die = true);

    // Plugins
    public function getPlugins($pluginFolder = '');
    public function getPluginData($pluginFile, $markup = true, $translate = true);
    public function addAction($tag, callable $functionToAdd, $priority = 10, $acceptedArgs = 1);
    public function doAction($tag, $arg = '');
    public function addMenuPage(
        $pageTitle,
        $menuTitle,
        $capability,
        $menuSlug,
        $function,
        $iconUrl = '',
        $position = null
    );
    public function addSubmenuPage(
        $parentSlug,
        $pageTitle,
        $menuTitle,
        $capability,
        $menuSlug,
        callable $function = null
    );
    public function removeSubmenuPage($menuSlug, $submenuSlug);
    public function pluginDirPath($file);
    public function pluginDirUrl($file);

    // Capabilities
    public function currentUserCan($capability, $objectId = null);

    // Settings and options
    public function registerSetting($optionGroup, $optionName, array $args = []);
    public function addSettingsSection($id, $title, callable $callback, $page);
    public function addSettingsField($id, $title, callable $callback, $page, $section = 'default', array $args = []);
    public function settingsFields($optionGroup);
    public function doSettingsSections($page);
    public function doSettingsFields($page, $section);
    public function getOption($option, $default = false);
    public function updateOption($option, $value, $autoload = null);
    public function setTransient($transient, $value, $expiration = 0);
    public function getTransient($transient);
    public function deleteTransient($transient);

    // Rendering
    public function submitButton(
        $text = null,
        $type = 'primary',
        $name = 'submit',
        $wrap = true,
        $otherAttributes = null
    );

    // Formatting
    public function escAttr($text);
    public function escHtml($text);
    public function escUrl($url, array $protocols = null, $_context = 'display');

    // Link Templates
    public function adminUrl($path = '', $scheme = 'admin');
    public function getPermalink($post = 0, $leaveName = false);
    public function getSiteUrl($blogId = null, $path = '', $scheme = null);

    // HTTP
    public function wpRemoteRequest($url, array $args = []);
    public function wpRemoteGet($url, array $args = []);
    public function wpRemoteHead($url, array $args = []);
    public function wpRemotePost($url, $args = []);
    public function wpRemoteRetrieveBody($response);
    public function wpRemoteRetrieveHeaders($response);
    public function wpRemoteRetrieveHeader($response, $header);
    public function wpRemoteRetrieveResponseCode($response);
    public function wpRemoteRetrieveResponseMessage($response);
    public function wpRemoteRetrieveCookies($response);
    public function wpRemoteRetrieveCookie($response, $name);
    public function wpRemoteRetrieveCookieValue($response, $name);

    // Scripts
    public function wpEnqueueScript($handle, $src = '', array $deps = [], $ver = false, $inFooter = false);
    public function wpLocalizeScript($handle, $objectName, $l10n);

    // Miscellaneous
    public function errorLog($logEntry);
}
