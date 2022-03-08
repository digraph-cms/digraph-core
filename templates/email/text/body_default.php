<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\UI\Templates;

/** @var Email */
$email = Context::fields()['email'];

echo $email->body_text();

echo PHP_EOL;
echo PHP_EOL;
echo '==========';
echo '==========';
echo PHP_EOL;
echo PHP_EOL;

if (Templates::exists('/email/text/footer_' . $email->category() . '.php')) {
    echo Templates::render('/email/text/footer_' . $email->category() . '.php');
} else {
    echo Templates::render('/email/text/footer_default.php');
}

echo PHP_EOL;
echo PHP_EOL;
echo '==========';
echo PHP_EOL;
echo "Email ID: " . $email->uuid();
