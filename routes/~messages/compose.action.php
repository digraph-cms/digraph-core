<h1>Compose direct message</h1>

<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\UserField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\Messaging\Message;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Users\Users;

Context::response()->private(true);

$to = (new UserField('Recipient'))
    ->setRequired(true);

$subject = (new Field('Subject line'))
    ->setRequired(true);

$body = (new RichContentField('Body', null, true))
    ->setRequired(true);

$form = (new FormWrapper)
    ->addChild($to)
    ->addChild($subject)
    ->addChild($body)
    ->addCallback(function () use ($to, $subject, $body) {
        $message = new Message(
            $subject->value(),
            Users::get($to->value()),
            $body->value(),
            'dm'
        );
        $message->setSender(Users::current());
        $message->send();
    });

$form->button()
    ->setText('Send message');

echo $form;
