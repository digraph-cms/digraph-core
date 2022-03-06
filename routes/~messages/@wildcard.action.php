<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Messaging\Messages;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;

Context::response()->private(true);

if (substr(Context::url()->action(), 0, 4) != 'msg_') {
    throw new HttpError(404);
}

$message = Messages::get(substr(Context::url()->action(), 4));
if (!$message) {
    throw new HttpError(404);
}

// set generic breadcrumb name
$url = Context::url();
$url->setName('Message');
Breadcrumb::top($url);
Context::fields()['page.name'] = 'Message';

// if message is sensitive, only allow it to signed in users
if ($message->sensitive()) {
    if (Session::user() != $message->recipientUUID()) {
        Permissions::requireMetaGroup('messages__admin');
    }
    if ($message->senderUUID() && Session::user() != $message->senderUUID()) {
        Permissions::requireMetaGroup('messages__admin');
    }
}

// mark as read if this is the recipient
if (!$message->read() && Session::user() == $message->recipientUUID()) {
    $message->setRead(true);
    $message->update();
}

// if user is allowed to view message, show its subject in breadcrumb
$url = Context::url();
$url->setName($message->subject());
Breadcrumb::top($url);
Context::fields()['page.name'] = $message->subject();

// set breadcrumb parent to archive if message is archived
if ($message->archived()) {
    Breadcrumb::parent(new URL('/~messages/archive.html'));
}

echo $message;
