<?php

namespace StackPath\WordPress\Integration;

/**
 * Interact with a local WordPress instance
 *
 * This assumes that WordPress is loaded into the global scope.
 */
class LocalWordPress implements WordPressInterface
{
    public function isWpError($thing)
    {
        return is_wp_error($thing);
    }

    public function wpDie($message = '', $title = '', $args = [])
    {
        wp_die($message, $title, $args);
    }

    public function getFileData($file, array $defaultHeaders, $context = '')
    {
        return get_file_data($file, $defaultHeaders, $context);
    }

    public function wpCreateNonce($action = -1)
    {
        return wp_create_nonce($action);
    }

    public function wpVerifyNonce($nonce, $action = -1)
    {
        return wp_verify_nonce($nonce, $action);
    }

    public function wpRedirect($location, $status = 302, $xRedirectBy = 'WordPress')
    {
        return wp_redirect($location, $status, $xRedirectBy);
    }

    public function checkAjaxReferer($action = -1, $queryArg = false, $die = true)
    {
        return check_ajax_referer($action, $queryArg, $die);
    }

    public function getPlugins($pluginFolder = '')
    {
        return get_plugins($pluginFolder);
    }

    public function getPluginData($pluginFile, $markup = true, $translate = true)
    {
        return get_plugin_data($pluginFile, $markup, $translate);
    }

    public function addAction($tag, callable $functionToAdd, $priority = 10, $acceptedArgs = 1)
    {
        return add_action($tag, $functionToAdd, $priority, $acceptedArgs);
    }

    public function doAction($tag, $arg = '')
    {
        do_action($tag, $arg);
    }

    public function addSubmenuPage(
        $parentSlug,
        $pageTitle,
        $menuTitle,
        $capability,
        $menuSlug,
        callable $function = null
    ) {
        $callable = $function === null ? '' : $function;

        return add_submenu_page(
            $parentSlug,
            $pageTitle,
            $menuTitle,
            $capability,
            $menuSlug,
            $callable
        );
    }

    public function removeSubmenuPage($menuSlug, $submenuSlug)
    {
        return remove_submenu_page($menuSlug, $submenuSlug);
    }

    public function pluginDirPath($file)
    {
        return plugin_dir_path($file);
    }

    public function pluginDirUrl($file)
    {
        return plugin_dir_url($file);
    }

    public function currentUserCan($capability, $objectId = null)
    {
        return current_user_can($capability, $objectId);
    }

    public function registerSetting($optionGroup, $optionName, array $args = [])
    {
        register_setting($optionGroup, $optionName, $args);
    }

    public function addSettingsSection($id, $title, callable $callback, $page)
    {
        add_settings_section($id, $title, $callback, $page);
    }

    public function addSettingsField($id, $title, callable $callback, $page, $section = 'default', array $args = [])
    {
        add_settings_field($id, $title, $callback, $page, $section, $args);
    }

    public function settingsFields($optionGroup)
    {
        ob_start();
        settings_fields($optionGroup);
        return ob_get_clean();
    }

    public function doSettingsSections($page)
    {
        ob_start();
        do_settings_sections($page);
        return ob_get_clean();
    }

    public function doSettingsFields($page, $section)
    {
        ob_start();
        do_settings_fields($page, $section);
        return ob_get_clean();
    }

    public function getOption($option, $default = false)
    {
        return get_option($option, $default);
    }

    public function updateOption($option, $value, $autoload = null)
    {
        return update_option($option, $value, $autoload);
    }

    public function setTransient($transient, $value, $expiration = 0)
    {
        return set_transient($transient, $value, $expiration);
    }

    public function getTransient($transient)
    {
        return get_transient($transient);
    }

    public function deleteTransient($transient)
    {
        return delete_transient($transient);
    }

    public function submitButton(
        $text = null,
        $type = 'primary',
        $name = 'submit',
        $wrap = true,
        $otherAttributes = null
    ) {
        ob_start();
        submit_button($text, $type, $name, $wrap, $otherAttributes);
        return ob_get_clean();
    }

    public function addMenuPage(
        $pageTitle,
        $menuTitle,
        $capability,
        $menuSlug,
        $function,
        $iconUrl = '',
        $position = null
    ) {
        return add_menu_page($pageTitle, $menuTitle, $capability, $menuSlug, $function, $iconUrl, $position);
    }

    public function escAttr($text)
    {
        return esc_attr($text);
    }

    public function escHtml($text)
    {
        return esc_html($text);
    }

    public function escUrl($url, array $protocols = null, $_context = 'display')
    {
        return esc_url($url, $protocols, $_context);
    }

    public function adminUrl($path = '', $scheme = 'admin')
    {
        return admin_url($path, $scheme);
    }

    public function getPermalink($post = 0, $leaveName = false)
    {
        return get_permalink($post, $leaveName);
    }

    public function getSiteUrl($blogId = null, $path = '', $scheme = null)
    {
        return get_site_url($blogId, $path, $scheme);
    }

    public function wpRemoteRequest($url, array $args = [])
    {
        return wp_remote_request($url, $args);
    }

    public function wpRemoteGet($url, array $args = [])
    {
        return wp_remote_get($url, $args);
    }

    public function wpRemoteHead($url, array $args = [])
    {
        return wp_remote_head($url, $args);
    }

    public function wpRemotePost($url, $args = [])
    {
        return wp_remote_post($url, $args);
    }

    public function wpRemoteRetrieveBody($response)
    {
        return wp_remote_retrieve_body($response);
    }

    public function wpRemoteRetrieveHeaders($response)
    {
        return wp_remote_retrieve_headers($response);
    }

    public function wpRemoteRetrieveHeader($response, $header)
    {
        return wp_remote_retrieve_header($response, $header);
    }

    public function wpRemoteRetrieveResponseCode($response)
    {
        return wp_remote_retrieve_response_code($response);
    }

    public function wpRemoteRetrieveResponseMessage($response)
    {
        return wp_remote_retrieve_response_message($response);
    }

    public function wpRemoteRetrieveCookies($response)
    {
        return wp_remote_retrieve_cookies($response);
    }

    public function wpRemoteRetrieveCookie($response, $name)
    {
        return wp_remote_retrieve_cookie($response, $name);
    }

    public function wpRemoteRetrieveCookieValue($response, $name)
    {
        return wp_remote_retrieve_cookie_value($response, $name);
    }

    public function wpEnqueueScript($handle, $src = '', array $deps = [], $ver = false, $inFooter = false)
    {
        wp_enqueue_script($handle, $src, $deps, $ver, $inFooter);
    }

    public function wpLocalizeScript($handle, $objectName, $l10n)
    {
        return wp_localize_script($handle, $objectName, $l10n);
    }

    public function errorLog($logEntry)
    {
        error_log($logEntry);
    }
}
