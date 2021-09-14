<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\Users;
use Formward\Fields\Input;

$user = Users::get(Context::arg('user') ?? Session::user());
if (!$user) {
    throw new HttpError(404, "User not found");
}

echo "<h1>Edit profile: " . $user->name() . "</h1>";

$form = new Form('Edit profile');
$form['name'] = new Input('Display name');
$form['name']->default($user->name());

if ($form->handle()) {
    $user->name($form['name']->value());
    $user->update();
    Notifications::flashConfirmation('Profile changes saved');
    throw new RefreshException();
} else {
    echo $form;
}
