<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Messaging\Messages;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;

Context::response()->private(true);

$message = Messages::get(Context::url()->action());
if (!$message) throw new HttpError(404);

// set generic breadcrumb name
$url = Context::url();
$url->setName('Message');
Breadcrumb::top($url);
Context::fields()['page.name'] = 'Message';

// if message is sensitive, only allow it to signed in users
if ($message->sensitive()) {
    if (Session::uuid() != $message->recipientUUID()) {
        Permissions::requireMetaGroup('messages__admin');
    }
    if ($message->senderUUID() && Session::uuid() != $message->senderUUID()) {
        Permissions::requireMetaGroup('messages__admin');
    }
}

// mark as read if this is the recipient
if (!$message->read() && Session::uuid() == $message->recipientUUID()) {
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
