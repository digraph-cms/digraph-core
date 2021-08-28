<h1>Sign out</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;

Session::deauthenticate('Used signout link');

$bounce = Context::arg('_bounce');
if ($bounce) {
    $bounce = new URL($bounce);
} else {
    $bounce = new URL('/');
}
Context::response()->redirect($bounce);
