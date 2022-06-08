<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\URL\URL;

/** @var Email */
$email = Context::fields()['email'];

echo "<small>";
echo "This email was sent to " . $email->to() . " by the website " . new URL('/');
echo '<br>';
echo '<br>';
printf('<a href="%s">unsubscribe from emails like this</a>', $email->url_unsubscribe());
echo '<br>';
printf('<a href="%s">manage your email settings</a>', $email->url_manageSubscriptions());
echo "</small>";
