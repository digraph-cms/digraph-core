<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\User;

/** @var User */
$user = Context::fields()['user'];

?>
<h1>Sign-in method added</h1>

<p>A new sign-in method was added to your account at <?php echo URLs::site(); ?>, using the sign-in method <code><?php echo Context::fields()['source']; ?></code></p>

<p>
    If you are aware of this change and recognize this account, no action is required.
    Otherwise you should review your account settings at:
</p>
<ul>
    <li><?php echo (new URL('/~user/authentication_methods.html?user=' . $user->uuid()))->html(); ?></li>
    <?php
    if (!Config::get('php_session.enabled')) {
        echo "<li>";
        echo (new URL('/~user/authentication_log.html?user=' . $user->uuid()))->html();
        echo "</li>";
    }
    ?>
</ul>