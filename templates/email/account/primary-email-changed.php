<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\User;

/** @var User */
$user = Context::fields()['user'];
/** @var string */
$email = Context::fields()['email'];

?>
<h1>Account primary email changed</h1>

<p>The email address <code><?php echo $email;?></code> has been set as the primary email address for your account on <?php echo URLs::site(); ?></p>

<?php echo Templates::render('/email/account/security-notice.php'); ?>
