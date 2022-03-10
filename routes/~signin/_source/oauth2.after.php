<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Users\Users;

// look through resource owner array for emails, add them to user
$user = Users::current();
foreach (Context::data('oauth_resource_owner')->toArray() as $key => $value) {
    if (stripos($key, 'email') !== false && filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $count = count($user->emails());
        $user->addEmail(
            $value,
            'Added from ' . Config::get("user_sources.oauth2.providers." . Context::arg('_provider') . ".name") . ' via OAuth signin',
            true
        );
    }
}
$user->update();
