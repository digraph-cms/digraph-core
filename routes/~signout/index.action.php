<h1>Sign out</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\Users;

Session::deauthenticate('Used signout link');

$bounce = Context::arg('_bounce');
if ($bounce) {
    $bounce = new URL($bounce);
    if (!Permissions::url($bounce, Users::guest())) {
        $bounce = new URL('/');
    }
} else {
    $bounce = new URL('/');
}

Context::response()->redirect($bounce);
