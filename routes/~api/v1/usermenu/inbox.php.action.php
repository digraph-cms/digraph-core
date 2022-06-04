<?php

use DigraphCMS\DB\DB;
use DigraphCMS\Messaging\Messages;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
use DigraphCMS\URL\URL;

echo "<div id='inbox-dropdown' class='navigation-frame navigation-frame--stateless' data-target='frame'>";

$messages = Messages::notify();
if (!$messages->count()) {
    Notifications::printNotice('No unread messages');
    echo "<script>document.getElementsByClassName('menuitem--inbox--notify')[0].style.display = 'none';</script>";
} else {
    echo "<div class='inbox-dropdown__controls toolbar'>";
    echo new ToolbarLink(
        'Inbox',
        'inbox',
        null,
        new URL('/~messages/')
    );
    echo new ToolbarLink(
        'Archive',
        'archive',
        null,
        new URL('/~messages/archive.html')
    );
    echo new ToolbarSpacer;
    echo new ToolbarLink(
        'All read',
        'done-all',
        function () use ($messages) {
            DB::beginTransaction();
            foreach ($messages as $message) {
                $message->setRead(true);
                $message->update();
            }
            DB::commit();
        }
    );
    echo "</div>";
    foreach ($messages as $message) {
        echo "<div class='inbox-dropdown__message'>";
        echo sprintf(
            "<a href='%s' data-target='_top' class='inbox-dropdown__message__subject'>%s</a>",
            $message->url(),
            $message->subject()
        );
        echo "<div class='inbox-dropdown__message__controls'>";
        echo new ToolbarLink(
            'Mark read',
            'mark-read',
            function () use ($message) {
                $message->setRead(true);
                $message->update();
            },
            null,
            $message->uuid() . 'read'
        );
        echo "</div>";
        echo "</div>";
    }
}

echo "</div>";
