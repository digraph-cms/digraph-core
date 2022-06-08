<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\URL\URL;

/** @var Email */
$email = Context::fields()['email'];

echo "This email was sent to " . $email->to() . " by the website " . new URL('/');
echo PHP_EOL;
echo PHP_EOL;
echo "If you would like to unsubscribe from emails like this, visit:" . PHP_EOL;
echo $email->url_unsubscribe();
echo PHP_EOL;
echo PHP_EOL;
echo "Or manage your unsubscription settings at:" . PHP_EOL;
echo $email->url_manageSubscriptions();
