<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\User;

/** @var User */
$user = Context::fields()['user'];
/** @var string */
$email = Context::fields()['email'];
/** @var URL */
$link = Context::fields()['link'];

?>
<h1>Verify your email address</h1>

<p>Please verify your email address on <?php echo URLs::site(); ?></p>

<a href="<?php echo $link; ?>" class="button">Verify your email</a>

<p>
    If the link above did not render correctly, you can copy and paste the URL:<br>
    <?php echo $link; ?>
</p>

<?php echo Templates::render('/email/account/security-notice.php'); ?>
