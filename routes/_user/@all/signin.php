<?php
include $this->helper('routing')->hookFile('_user', 'core_init.php')['file'];

//end if user is already signed in
if ($users->user()) {
    $package->redirect(
        $this->helper('urls')->parse('_user'),
        303
    );
    return;
}

//check that signin is allowed with this manager
if (!$this->helper("users")->signinAllowed($managerName)) {
    $package->error(404);
    return;
}

//build form
$form = new Formward\Form('', 'signin-'.$managerName);

//check for form setup pre-hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signin_form_pre.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signin_form_pre.php') as $file) {
    include $file['file'];
}

if ($form) {
    //default form settings
    $form['email'] = new Formward\Fields\Email('Email address');
    $form['email']->required();
    $form['password'] = new Formward\Fields\Password('Password');
    $form['password']->required();
}

//check for form setup post-hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signin_form_post.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signin_form_post.php') as $file) {
    include $file['file'];
}

//output form
if ($form) {
    echo $form;
}

if ($form && $form->handle()) {
    //check for handle hooks
    foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signin_handle.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles('_user', 'signin_handle.php') as $file) {
        include $file['file'];
    }
    //do default signin with password if none of the above completed a signin
    if (!$users->id() && $form) {
        $matches = $users->getByEmail($form['email']->value());
        $done = false;
        foreach ($matches as $u) {
            if ($u->checkPassword($form['password']->value())) {
                //sign in was successful
                $users->id($u->id());
                $done = true;
                $package->redirect(
                    $this->helper('urls')->parse('_user')
                );
                break;
            }
        }
        //sign in failed
        if (!$done) {
            $this->helper('notifications')->error(
                $this->helper('strings')->string('user.signin_failed')
            );
        }
    }
}

//check for hooks regarding user being signed in
if ($users->id()) {
    //check for signed in hooks
    foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signin_complete.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles('_user', 'signin_complete.php') as $file) {
        include $file['file'];
    }
}

//redirect if user is signed in
if ($users->user()) {
    $package->redirect(
        $this->helper('urls')->parse('_user'),
        303
    );
    return;
}
