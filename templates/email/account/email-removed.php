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
<h1>Email removed from account</h1>

<p>The email address <code><?php echo $email;?></code> has been removed from your account on <?php echo URLs::site(); ?></p>

<?php echo Templates::render('/email/account/security-notice.php'); ?>
