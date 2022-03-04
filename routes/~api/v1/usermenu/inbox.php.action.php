<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Icon;
use DigraphCMS\Messaging\Messages;
use DigraphCMS\UI\Notifications;

Context::response()->template('framed.php');

echo "<div id='inbox-dropdown' class='navigation-frame navigation-frame--stateless' data-target='frame'>";

$messages = Messages::notify();
if (!$messages->count()) {
    Notifications::printNotice('No unread messages');
} else {
    foreach ($messages as $message) {
        echo "<div class='inbox-dropdown__message'>";
        echo sprintf(
            "<a href='%s' data-target='_top' class='inbox-dropdown__message__subject'>%s%s%s</a>",
            $message->url(),
            $message->important() ? new Icon('important') : '',
            $message->sensitive() ? new Icon('secure') : '',
            $message->subject()
        );
        echo "</div>";
    }
}

echo "</div>";
