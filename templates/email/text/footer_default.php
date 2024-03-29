<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\URL\URL;

/** @var Email */
$email = Context::fields()['email'];

if ($email->category() == 'service') {
    echo "This service-related email was sent to " . $email->to() . " by the website " . new URL('/');
} else {
    echo "This " . $email->categoryLabel() . " email was sent to " . $email->to() . " by the website " . new URL('/');
    if (!$email->isService()) {
        echo PHP_EOL;
        echo PHP_EOL;
        echo "If you would like to unsubscribe from emails like this, visit:" . PHP_EOL;
        echo $email->url_unsubscribe();
    }
}
echo PHP_EOL;
echo PHP_EOL;
echo "Manage your email settings at:" . PHP_EOL;
echo $email->url_manageSubscriptions();
