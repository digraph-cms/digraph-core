<?php

use DigraphCMS\Config;
use DigraphCMS\URL\URL;

?>
<hr>

<p>
    This is an automatically-generated email sent in response to a change to your account.
    If you are aware of this activity no additional action is required.
    Otherwise you should review your account for suspicious activity at:
</p>

<ul>
    <li><?php echo (new URL('/~users/profile/authentication_methods.html'))->html(); ?></li>
    <?php
    if (!Config::get('php_session.enabled')) {
        echo "<li>";
        echo (new URL('/~users/profile/authentication_log.html'))->html();
        echo "</li>";
    }
    ?>
</ul>