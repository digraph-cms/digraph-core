<?php

use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\Users;

$user = Users::get(Context::arg('user') ?? Session::user());
if (!$user) {
    throw new HttpError(404, "User not found");
}

echo "<h1>Edit profile: " . $user->name() . "</h1>";

$form = new FormWrapper();
$name = (new Field('Display name'))
    ->setDefault($user->name())
    ->setRequired(true);
$form->addChild($name);

Dispatcher::dispatchEvent('onEditProfileForm', [$form, $user]);

if ($form->ready()) {
    $user->name($name->value());
    $user->update();
    Notifications::flashConfirmation('Profile changes saved');
    throw new RefreshException();
}
echo $form;
