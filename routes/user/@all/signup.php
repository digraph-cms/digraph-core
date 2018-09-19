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

$form['email'] = new Formward\Fields\Email('Email address');
$form['email']->required();
$form['username'] = new Formward\Fields\Input('Username');
$form['username']->required();
$form['password'] = new Formward\Fields\ConfirmedPassword('');
$form['password']->required();

//set up validators
// $form['email']->addValidatorFunction('unique',function());

//check for form post-hooks
foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signup_form_post.php') as $file) {
    include $file['file'];
}

echo $form;

if ($form->handle()) {
    //check for handle pre hooks
    foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signup_handle_pre.php') as $file) {
        if (include($file['file']) === false) {
            return;
        }
    }
    //create user
    $manager->create(
        $form['username']->value(),
        $form['email']->value(),
        $form['password']->value()
    );
    //check for handle post hooks
    foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signup_handle_post.php') as $file) {
        if (include($file['file']) === false) {
            return;
        }
    }
}
