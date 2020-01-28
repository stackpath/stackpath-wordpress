<?php

if (!defined('ABSPATH')) {
    die('!');
}

/** @var \StackPath\WordPress\Plugin $this */
/** @var \StackPath\WordPress\TransientData $transientData */

$autoPurgeChecked = $this->settings->autoPurgeContent;
if ($transientData->getFormData('auto_purge') !== null) {
    $autoPurgeChecked = $transientData->getFormData('auto_purge');
}

$bypassCacheChecked = $this->settings->bypassCacheOnWordPressCookies;
if ($transientData->getFormData('bypass_cache') !== null) {
    $bypassCacheChecked = $transientData->getFormData('bypass_cache');
}

?>

<h1>Purge Cache</h1>

<div class="stackpath-dashboard-panel">
    <div>Purging content removes all site content cached at the StackPath CDN and is irreversible. StackPath will re-cache your site content the next time your users request it.</div>

    <div style="padding-bottom: 1em">Most purge requests are immediate, but larger sites may take a few minutes to purge from all of StackPath's CDN edge nodes.</div>

    <h2>Purge Everything</h2>

    <p>Purge all of site content cached on the StackPath CDN.</p>

    <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post">
        <input type="hidden" name="action" value="stackpath_purge_everything">
        <input type="hidden" name="stackpath_purge_everything_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_purge_everything_nonce') ?>">

         <input type="submit" name="submit" id="submit" class="button button-primary" style="margin-bottom: 3em" value="Purge All Content">
    </form>

    <h2>Custom Purge</h2>

    <p>Purge some of your site's content from the StackPath CDN based on its path.</p>

    <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post">
        <input type="hidden" name="action" value="stackpath_custom_purge">
        <input type="hidden" name="stackpath_custom_purge_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_custom_purge_nonce') ?>">

        <label>
            <p>Add paths relative to your site root on each line, for example <code>/</code> or <code>/images/</code>. If the path is a folder, then all files in that folder will be purged from the CDN.</p>
            <textarea name="paths" cols="50" rows="5" required><?=$transientData->getFormData('paths')?></textarea>
        </label>

        <div style="margin-top: 20px">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Purge Content">
        </div>
    </form>
</div>

<h1>Auto Purge</h1>

<div class="stackpath-dashboard-panel">
    <div>Automatically purge pages and posts as they're updated in WordPress.</div>

    <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post">
        <input type="hidden" name="action" value="stackpath_auto_purge">
        <input type="hidden" name="stackpath_auto_purge_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_auto_purge_nonce') ?>">

        <p>
            <label>
                <input type="checkbox" name="auto_purge" <?=$autoPurgeChecked ? 'checked' : ''?>> Auto-purge new and changed content
            </label>
        </p>

        <div style="margin-top: 20px">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </div>
    </form>
</div>

<h1>Bypass Cache for WordPress Cookies</h1>

<div class="stackpath-dashboard-panel">
    <div>Proxy all requests to your origin server when the following cookies exist in the request:</div>

    <p><code>wp-*, wordpress, comment_*, woocommerce_*</code></p>

    <div>This ensures all dynamic content comes from the origin instead of the cache.</div>

    <form action="<?= $this->wordPress->escUrl($this->wordPress->adminUrl('admin-post.php')) ?>" method="post">
        <input type="hidden" name="action" value="stackpath_bypass_cache_on_wordpress_cookie">
        <input type="hidden" name="stackpath_bypass_cache_on_wordpress_cookie_nonce" value="<?= $this->wordPress->wpCreateNonce('stackpath_bypass_cache_on_wordpress_cookie_nonce') ?>">

        <p>
            <label>
                <input type="checkbox" name="bypass_cache"  <?=$bypassCacheChecked ? 'checked' : ''?>> Bypass cache on WordPress cookies
            </label>
        </p>

        <div style="margin-top: 20px">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
        </div>
    </form>
</div>
