<h1>Send an email to a user</h1>
<p>
    Emails are added to the email queue.
    This way they ensure that both sending and queueing are working properly.
    They may take as long as several minutes to actually be sent though.
</p>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\UserField;
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

if ($form->ready()) {
    $emails = Email::newForUser_all(
        'debug',
        Users::get($user->value()),
        $subject->value(),
        $body->value()
    );
    Emails::queue($emails);
}

echo $form;
