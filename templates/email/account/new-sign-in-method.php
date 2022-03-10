<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\User;

/** @var User */
$user = Context::fields()['user'];

?>
<h1>Sign-in method added</h1>

<p>A new sign-in method was added to your account at <?php echo URLs::site(); ?>, using the sign-in method <code><?php echo Context::fields()['source']; ?></code></p>

<?php echo Templates::render('/email/account/security-notice.php'); ?>