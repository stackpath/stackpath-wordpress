<?php

if (!defined('ABSPATH')) {
    die('!');
}

/** @var \StackPath\WordPress\Plugin $this */
?>

<h1>Accelerate and protect your WordPress website with StackPath</h1>

<div class="stackpath-dashboard-panel">
    <div class="stackpath-features-container">
        <div class="stackpath-feature">
            <img class="stackpath-feature-image" src="<?=$this->assetUrl('cdn.svg')?>" alt="Content Delivery Network">
            <h2>Content Delivery Network</h2>
            <p>Deliver your assets from all over the world with our enterprise grade CDN.</p>
        </div>

        <div class="stackpath-feature">
            <img class="stackpath-feature-image" src="<?=$this->assetUrl('waf.svg')?>" alt="Web Application Firewall">
            <h2>Web Application Firewall</h2>
            <p>Protect your website origin with every dynamic request inspected at the edge.</p>
        </div>

        <div class="stackpath-feature">
            <img class="stackpath-feature-image" src="<?=$this->assetUrl('dns.svg')?>" alt="Global Managed DNS">
            <h2>Global Managed DNS</h2>
            <p>Always on-line DNS delivered from our global anycast DNS platform.</p>
        </div>

        <div class="stackpath-feature">
            <img class="stackpath-feature-image" src="<?=$this->assetUrl('monitoring.svg')?>" alt="Origin Server Monitoring">
            <h2>Origin Server Monitoring</h2>
            <p>Monitor your hosting provider/origin to ensure 99.9% uptime.</p>
        </div>
    </div>

    <p style="text-align: center"><a class="button button-primary" href="<?=$this->wordPress->adminUrl('admin.php?page=stackpath-log-in') ?>">Log In</a></p>
<!--    <p style="text-align: center"><input type="submit" name="submit" id="submit" class="button button-primary" value="Create Account"></p>-->
<!--    <p style="text-align: center">Already have an account? <a href="<?=$this->wordPress->adminUrl('admin.php?page=stackpath-log-in') ?>">Log in</a></p>-->
</div>
