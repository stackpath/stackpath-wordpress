# StackPath WordPress Plugin

<div>
    <img src="https://www.stackpath.com/content/images/logo-and-branding/stackpath-monogram-reversed-screen.svg" width="145" alt="StackPath">
    <img src="https://s.w.org/style/images/about/WordPress-logotype-standard.png" height="145px" alt="WordPress">
</div>

Place your WordPress site behind the power of the StackPath edge network. This plugin gives you performance analysis at-a-glance, web application firewall (WAF) control, and origin monitoring to ensure site uptime, reliability, and speed.

Once installed, this plugin scans your StackPath account for a matching site or lets you make new service to put in front of your WordPress installation. After the plugin is linked to your StackPath site you have the ability to:

* See overall and per-location CDN bandwidth and request metrics.
* Purge content from the CDN cache all at once, by path, and automatically as you change your site's content.
* Optionally skip CDN caching of requests with common WordPress cookies in them.
* See WAF analytics and recent security events against your site.
* Place the WAF into monitoring mode to enable and disable firewall service.
* Configure denial of service protection.
* Block access to your WordPress installation's admin area except for an allowed list of IP addresses or IP ranges.
* View your WordPress installation's uptime as monitored by StackPath's global edge network.

**Please Note:** This plugin is currently in beta. The following features are still in development:

* Installation from the admin area's plugins page
* Creating new StackPath service
* Firewall control and reporting
* Monitoring reporting

## Requirements

 * An active StackPath account with API credentials. Visit the [API management](control.stackpath.com/api-management) page in the StackPath customer portal to generate yours.
 * An active site on your StackPath account. This won't be a requirement after the beta period.
 * A working [WordPress](https://wordpress.org/) version 5.3.0+ installation running at least [PHP](https://php.net) version 5.6.

## Installation

### From WP-Admin

Coming soon!

### From Releases

[Releases](https://github.com/stackpath/stackpath-wordpress/releases) are zip files that contain the plugin built without testing, editor, GitHub, and other build files. Install from a release file to use an older or test version that's unavailable on the WordPress plugins directory.

* Create the `wp-content/plugins/stackpath-wordpress` directory in your WordPress installation.
* Download and extract the latest release zip file to the `wp-content/plugins/stackpath-wordpress` directory.

### From Source

Checking out the project from this repository is a great way to try out a bleeding edge version of the plugin or to contribute to the plugin. Installing from source requires the [composer](https://getcomposer.org/) dependency management utility.

* Create the `wp-content/plugins/stackpath-wordpress` directory in your WordPress installation.
* Check out this project's source into the `wp-content/plugins/stackpath-wordpress` directory.
* Run `composer dumpautoload -o` to generate the plugin's class auto-loader or run `composer install --dev` to build the autoloader and install all development utilities like `phpunit` and `phpcs`.

We do not recommend installing development utilities on a live site.

## Usage

1. Navigate to your WordPress installation's admin area. If the plugin is installed there should be a "StackPath" item in the menu on the left-hand side.
1. Click that link and run through the setup wizard to link the plugin with your StackPath account and services.
1. After the plugin is installed the StackPath menu item will have sub-menus to view and control your site's behavior on the StackPath network.

## Debugging

Set `WP_DEBUG` to `true` in your WordPress installation's `wp-config.php` file to enable a debug display in the "More" dropdown under the StackPath logo. This debug information includes some system information, a list of WordPres plugins installed, and the StackPath plugin's configuration.

## Contributing

We happily accept pull requests! Check out our [contributing guide](https://github.com/stackpath/stackpath-wordpress/blob/master/.github/contributing.md) if you'd like to help out.
