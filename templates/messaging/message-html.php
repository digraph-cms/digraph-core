<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;

/** @var DigraphCMS\Messaging\Message */
$message = Context::fields()['message'];

?>
<div class="message">
    <div class="message__subject">
        <?php
        echo $message->subject();
        ?>
    </div>
    <div class="message__time"><?php echo Format::datetime($message->time()); ?></div>
    <div class="message__sender"><?php echo $message->sender() ?? '<em>system</em>'; ?></div>
    <div class="message__recipient"><?php echo $message->recipient(); ?></div>
    <div class="message__body"><?php echo $message->body(); ?></div>
</div>