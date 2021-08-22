<h1>Sign out</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

Session::deauthorize();

$bounce = Context::arg('bounce');
if ($bounce) {
    $bounce = new URL($bounce);
}else {
    $bounce = new URL('/');
}
Context::response()->redirect($bounce);

Notifications::flashConfirmation('You are now signed out');
