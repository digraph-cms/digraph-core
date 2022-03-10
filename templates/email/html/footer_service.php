<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\URL\URL;

/** @var Email */
$email = Context::fields()['email'];

echo "<small>";
echo "This service-related email was sent to " . $email->to() . " by the website " . new URL('/') . PHP_EOL;
echo "</small>";
