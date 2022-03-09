<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\RichContent\RichContent;

Context::response()->template('framed.php');

$message = Email::newForEmail('service', 'joby@byjoby.com', 'Test email', new RichContent('# Test email' . PHP_EOL . PHP_EOL . 'This is a [test email](https://www.byjoby.com/).' . PHP_EOL . PHP_EOL . 'This is a [test email](https://www.byjoby.com/).'));

var_dump($message);

$message->send();
