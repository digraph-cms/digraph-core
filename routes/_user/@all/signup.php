<?php
include $this->helper('routing')->hookFile('_user', 'core_init.php')['file'];
$package->cache_noStore();

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
foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signup_form_pre.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signup_form_pre.php') as $file) {
    include $file['file'];
}

if ($form) {
    $form['email'] = new Formward\Fields\Email('Email address');
    $form['email']->required(true);
    $form['displayname'] = new Formward\Fields\Input('Display name');
    $form['displayname']->required(true);
    $form['password'] = new Formward\Fields\ConfirmedPassword('');
    $form['password']->required(true);

    //set up validators
    $form['email']->addValidatorFunction(
        'unique',
        function (&$field) {
            $value = $field->value();
            if ($this->helper('users')->getByEmail($field->value())) {
                $this->helper('notifications')->notice(
                    $this->helper('lang')->string(
                        'notifications.account_recovery',
                        ['link' => $this->helper('urls')->parse('_user/recover')->html()]
                    )
                );
                return $this->helper('lang')->string('forms.signup_email_taken');
            }
            return true;
        }
    );
}

//check for form post-hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signup_form_post.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signup_form_post.php') as $file) {
    include $file['file'];
}

if ($form) {
    echo $form;
}

if ($form && $form->handle()) {
    //set up new user
    $user = $manager->create();
    $user->name($form['displayname']->value());
    $user->addEmail($form['email']->value());
    $user->setPassword($form['password']->value());
    //check for handle pre hooks
    foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signup_handle.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles('_user', 'signup_handle.php') as $file) {
        include $file['file'];
    }
    //save new user
    if (!$user->insert()) {
        $package->error(500, 'Failed to insert user');
    }
    //redirect
    $package->redirect(
        $this->helper('urls')->parse('_user/signedup')
    );
}
