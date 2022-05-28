<h1>Inbox</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Messaging\Messages;
use DigraphCMS\Messaging\MessageTable;
use DigraphCMS\Session\Session;

Context::response()->private(true);

echo new MessageTable(
    Messages::query()
        ->inbox()
        ->where('${recipient} = ?', [Session::uuid()])
        ->order('${time} desc')
);
