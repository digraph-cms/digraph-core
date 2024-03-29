<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Emails;
use DigraphCMS\FS;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;

$email = Emails::get(Context::url()->actionSuffix());
if (!$email) throw new HttpError(404);

if ($email->error()) {
    Breadcrumb::parent(new URL('email_errors.html'));
} elseif ($email->sent()) {
    Breadcrumb::parent(new URL('sent_emails.html'));
} else {
    Breadcrumb::parent(new URL('queued_emails.html'));
}

echo "<h1>Email: " . $email->subject() . "</h1>";

if ($email->error()) {
    echo "<h2>Send error</h2>";
    Notifications::printError($email->error());
}

echo "<h2>Metadata</h2>";

echo "<dl>";
printf("<dt>%s</dt><dd>%s</dd>", 'Created', Format::datetime($email->time()));
printf("<dt>%s</dt><dd>%s</dd>", 'Sent', $email->sent() ? Format::datetime($email->sent()) : '<em>pending</em>');
printf("<dt>%s</dt><dd>%s</dd>", 'To', $email->to());
$email->cc() ? printf("<dt>%s</dt><dd>%s</dd>", 'CC', $email->cc()) : '';
$email->bcc() ? printf("<dt>%s</dt><dd>%s</dd>", 'BCC', $email->bcc()) : '';
printf("<dt>%s</dt><dd>%s</dd>", 'From', $email->from());
printf("<dt>%s</dt><dd>%s</dd>", 'Category', $email->categoryLabel());
$email->toUser() ? printf("<dt>%s</dt><dd>%s</dd>", 'Recipient user', $email->toUser()) : '';
echo "</dl>";

$file = new File(
    $email->uuid() . '.html',
    Emails::prepareBody_html($email),
    [
        'email_message_admin_view',
        $email->uuid(),
    ],
    function () {
        return Permissions::inMetaGroup('email__admin');
    }
);

echo "<h2>Rendered HTML</h2>";
echo "<iframe src='" . $file->url() . "' style='border:0;width:100%;' class='autosized-frame'></iframe>";

echo "<h2>Rendered plaintext</h2>";
$text = Emails::prepareBody_text($email);
echo "<pre>" . $text . "</pre>";
