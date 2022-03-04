<h1>Sent messages</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Messaging\Messages;
use DigraphCMS\Messaging\MessageTable;
use DigraphCMS\Session\Session;
use DigraphCMS\Users\Permissions;

Permissions::requireAuth();
Context::response()->private(true);

echo new MessageTable(
    Messages::select()
        ->where('sender = ?', [Session::user()])
        ->order('time desc'),
    true
);
