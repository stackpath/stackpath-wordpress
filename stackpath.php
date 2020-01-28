<?php

/**
 * Plugin Name: StackPath
 * Description: Place your WordPress site behind the StackPath edge network for easy CDN, firewall, and monitoring control
 * Plugin URI: https://github.com/stackpath/stackpath-wordpress
 * Version: 0.1.0
 * Author: StackPath, LLC
 * Author URI: https://www.stackpath.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires at least: 5.3.0
 * Requires PHP: 5.6
 */

use StackPath\API\Client;
use StackPath\WordPress\Integration\LocalWordPress;
use StackPath\WordPress\Plugin;
use StackPath\WordPress\Settings;

// Do not allow direct access to this file.
if (!defined('ABSPATH')) {
    die('!');
}

const STACKPATH_PLUGIN_REQUIRED_PHP_VERSION = '5.6';

// Perform system checks before loading the plugin.

// Make sure the system's PHP version is recent enough. Deactivate the plugin
// and exit if it isn't.
if (version_compare(PHP_VERSION, STACKPATH_PLUGIN_REQUIRED_PHP_VERSION, '<')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    deactivate_plugins(plugin_basename(__FILE__), true);

    wp_die(
        'The StackPath plugin requires at least PHP version '
            . STACKPATH_PLUGIN_REQUIRED_PHP_VERSION . ', but version '
            . PHP_VERSION . ' is installed.',
        'Unsupported PHP Version',
        ['back_link' => true]
    );
}

// Everything is good. Load the plugin.
include_once ABSPATH . 'wp-includes/pluggable.php';
require_once __DIR__ . '/vendor/autoload.php';

// Run the plugin in a closure to avoid polluting the global namespace.
$runStackPathPlugin = static function () {
    $settings = Settings::fromWordPressOptions(get_option(Settings::WP_OPTIONS_NAME));
    $wordPress = new LocalWordPress();
    $apiClient = new Client($settings, $wordPress);
    $plugin = new Plugin($settings, $apiClient, $wordPress);
    $plugin->run();
};

$runStackPathPlugin();
