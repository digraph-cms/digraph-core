<h1>Send an email to a user</h1>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\UserField;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Users\Users;

$form = new FormWrapper('debug_email');

$user = (new UserField('User'))
    ->setRequired(true)
    ->addForm($form);

$subject = (new Field('Subject'))
    ->setRequired(true)
    ->addForm($form);

$body = (new RichContentField('Body', 'debug_email_body'))
    ->setRequired(true)
    ->addForm($form);

$now = (new CheckboxField('Send immediately'))
    ->addTip('By default emails are queued instead of sending immediately')
    ->addForm($form);

if ($form->ready()) {
    $emails = Email::newForUser_all(
        'debug',
        Users::get($user->value()),
        $subject->value(),
        $body->value()
    );
    if ($now->value()) Emails::send($emails);
    else Emails::queue($emails);
}

echo $form;
