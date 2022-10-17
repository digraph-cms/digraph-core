<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\URL\URL;

/** @var Email */
$email = Context::fields()['email'];

echo "<small>";
if ($email->category() == 'service') {
    echo "This service-related email was sent to " . $email->to() . " by the website " . new URL('/');
} else {
    echo "This <em>" . $email->categoryLabel() . "</em> email was sent to " . $email->to() . " by the website " . new URL('/');
    if (!$email->isService()) {
        echo '<br>';
        echo '<br>';
        printf('<a href="%s">unsubscribe from emails like this</a>', $email->url_unsubscribe());
    }
}
echo '<br>';
echo '<br>';
printf('<a href="%s">manage your email settings</a>', $email->url_manageSubscriptions());
echo "</small>";
