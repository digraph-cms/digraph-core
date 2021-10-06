<?php

$package->cache_noStore();
/** @var \Digraph\Users\UserHelper */
$users = $cms->helper('users');
$postSignupUrl = $package->url()->getData() ?? $this->helper('urls')->parse('_user/signedup');

//end if user is already signed in
if ($users->id()) {
    $this->helper('notifications')->notice('You are already signed in');
    return;
}

//check that signup is allowed with this manager
$managerName = $package['url.args.manager'] ? $package['url.args.manager'] : $cms->config['users.defaultmanager'];
if (!($manager = $users->manager($managerName)) || !$users->signupAllowed($managerName)) {
    $package->error(500, 'UserManager ' . $managerName . ' not found or not allowed');
    return;
}

//build form
$form = new Formward\Form('', 'signup-' . $managerName);

//check for form pre-hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName . '/signup_form_pre.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signup_form_pre.php') as $file) {
    include $file['file'];
}

if ($form) {
    $form['email'] = new Formward\Fields\Email('Email address');
    $form['email']->required(true);
    $form['email']->addValidatorFunction(
        'unique',
        function ($field) use ($users) {
            if ($users->getByEmail($field->value())) {
                return "That email address is already registered";
            }
            return true;
        }
    );
    $form['displayname'] = new Formward\Fields\Input('Display name');
    $form['displayname']->required(true);
    $form['password'] = new Formward\Fields\ConfirmedPassword('');
    $form['password']->required(true);
    $form['password']->addValidatorFunction(
        'strength',
        function ($field) {
            if ($pw = $field->value()) {
                if (strlen($pw) < 8) {
                    return 'Password must be at least 8 characters';
                }
                $charTypes = 0;
                $charTypes += preg_match('/[a-z]/', $pw) ? 1 : 0;
                $charTypes += preg_match('/[A-Z]/', $pw) ? 1 : 0;
                $charTypes += preg_match('/[0-9]/', $pw) ? 1 : 0;
                $charTypes += preg_match('/[^a-zA-Z0-9]/', $pw) ? 1 : 0;
                if ($charTypes < 3) {
                    return "Password must contain at least three of the character types:<ul><li>upper case letters</li><li>lower case letters</li><li>numbers</li><li>special characters</li></ul>";
                }
            }
            return true;
        }
    );

    //set up validators
    $form['email']->addValidatorFunction(
        'unique',
        function ($field) use ($form) {
            $value = $field->value();
            if ($this->helper('users')->getByEmail($field->value())) {
                $this->helper('notifications')->notice(
                    $this->helper('strings')->string(
                        'notifications.account_recovery',
                        ['link' => $this->helper('urls')->parse('_user/recover?email=' . $form['email']->value())->html()]
                    )
                );
                return $this->helper('strings')->string('forms.signup.email_taken');
            }
            return true;
        }
    );
}

//check for form post-hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName . '/signup_form_post.php') as $file) {
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
    if ($form['displayname']) {
        $user->name($form['displayname']->value());
    }
    $user->addEmail($form['email']->value());
    $user->setPassword($form['password']->value());
    //check for handle pre hooks
    foreach ($this->helper('routing')->allHookFiles('_user', $managerName . '/signup_handle.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles('_user', 'signup_handle.php') as $file) {
        include $file['file'];
    }
    //save new user
    if (!$user->insert()) {
        $package->error(500, 'Failed to insert user');
    }
    //sign in as user
    $users->id($user->id());
    $cms->helper('notifications')->flashConfirmation('You are now signed up');
    //redirect
    $package->redirect($postSignupUrl);
}
