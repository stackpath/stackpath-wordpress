<?php

if (!defined('ABSPATH')) {
    die('!');
}

/** @var \StackPath\WordPress\Message $message */
/** @var string $messageType */
?>

<div class="updated <?= $messageType ?>">
    <p><strong><?= $message->title ?></strong></p>

    <?php if ($message->hasDescription()) : ?>
        <p><?= nl2br($message->description) ?></p>
    <?php endif; ?>
</div>
