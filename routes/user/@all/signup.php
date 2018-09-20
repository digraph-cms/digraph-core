<?php
include $this->helper('routing')->hookFile('user', 'core_init.php')['file'];

//end if user is already signed in
if ($users->id()) {
    $this->helper('notifications')->notice('You are already signed in');
    return;
}

//check that signup is allowed with this manager
if (!$this->helper("users")->signupAllowed($managerName)) {
    $package->error(404);
    return;
}

//build form
$form = new Formward\Form('', 'signup-'.$managerName);

//check for form pre-hooks
foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signup_form_pre.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('user', 'signup_form_pre.php') as $file) {
    include $file['file'];
}

if ($form) {
    $form['email'] = new Formward\Fields\Email('Email address');
    $form['email']->required();
    $form['username'] = new Formward\Fields\Input('Username');
    $form['username']->required();
    $form['password'] = new Formward\Fields\ConfirmedPassword('');
    $form['password']->required();

    //set up validators
    // $form['email']->addValidatorFunction('unique',function());
}

//check for form post-hooks
foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signup_form_post.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('user', 'signup_form_post.php') as $file) {
    include $file['file'];
}

if ($form) {
    echo $form;
}

if ($form && $form->handle()) {
    //check for handle pre hooks
    foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signup_handle.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles('user', 'signup_handle.php') as $file) {
        include $file['file'];
    }
}
