<?php
include $this->helper('routing')->hookFile('user', 'init.php')['file'];

//end if user is already signed in
if ($users->id()) {
    $this->helper('notifications')->notice('You are already signed in.');
    return;
}

//build form
$form = new Formward\Form('');

$form['email'] = new Formward\Fields\Email('Email address');
$form['email']->required();
$form['password'] = new Formward\Fields\Password('Password');
$form['password']->required();

echo $form;
