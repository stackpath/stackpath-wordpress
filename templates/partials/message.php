<?php

if (!defined('ABSPATH')) {
    die('!');
}

use StackPath\WordPress\Message;

/** @var \StackPath\WordPress\Message $message */
/** @var string $messageType */
?>

<div class="updated <?= $messageType ?>">
    <p><strong><?= $message->title ?></strong></p>

    <?php if ($message->hasDescription()) : ?>
        <p><?= nl2br($message->description) ?></p>
    <?php endif; ?>

    <?php if (
        $message->hasDebugInformation()
        && defined('WP_DEBUG')
        && defined('WP_DEBUG_DISPLAY')
        && WP_DEBUG
        && WP_DEBUG_DISPLAY
    ) : ?>
        <p><strong>Debug Information</strong></p>
        <p>You are seeing this because both <code>WP_DEBUG</code> and <code>WP_DEBUG_DISPLAY</code> are set to <code>true</code> in <code>wp-config.php</code>.</p>

        <div class="stackpath-message-debug-information">
            <?php foreach ($message->debugInformation as $key => $value) : ?>
                <strong><?= $key ?></strong>
                <pre class="stackpath-message-debug-information-value"><?= htmlentities(Message::debugFormat($value)) ?></pre>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
