<?php

use DigraphCMS\Context;
use DigraphCMS\Users\Users;

$user = Users::get(Context::arg('uuid'));
if (!$user) {
    Context::error(404);
}

// TODO: display user info in a more useful way
var_dump($user);
