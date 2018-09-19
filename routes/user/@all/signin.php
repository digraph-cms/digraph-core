<?php
include $this->helper('routing')->hookFile('user', 'core_init.php')['file'];

//end if user is already signed in
if ($users->id()) {
    $this->helper('notifications')->notice('You are already signed in.');
    return;
}

//check that signin is allowed with this manager
if (!$this->helper("users")->signinAllowed($managerName)) {
    $package->error(404);
    return;
}

//build form
$form = new Formward\Form('', 'signin-'.$managerName);

//check for form pre-hooks
foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signin_form_pre.php') as $file) {
    include $file['file'];
}

$form['email'] = new Formward\Fields\Email('Email address');
$form['email']->required();
$form['password'] = new Formward\Fields\Password('Password');
$form['password']->required();

//check for form post-hooks
foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signin_form_post.php') as $file) {
    include $file['file'];
}

echo $form;

if ($form->handle()) {
    //check for handle pre hooks
    foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signin_handle_pre.php') as $file) {
        if (include($file['file']) === false) {
            return;
        }
    }
    //check for handle post hooks
    foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signin_handle_post.php') as $file) {
        if (include($file['file']) === false) {
            return;
        }
    }
}
