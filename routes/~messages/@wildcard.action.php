<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Messaging\Messages;
use DigraphCMS\UI\Breadcrumb;

Context::response()->private(true);

$message = Messages::get(Context::url()->action());
if (!$message) {
    throw new HttpError(404);
}

// set generic breadcrumb name
$url = Context::url();
$url->setName('Message');
Breadcrumb::top($url);

// TODO: check permissions and such

// if user is allowed to view message, show its subject in breadcrumb
$url = Context::url();
$url->setName($message->subject());
Breadcrumb::top($url);

echo $message;
