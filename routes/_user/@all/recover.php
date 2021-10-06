<?php
$package->cache_noStore();

/** @var \Digraph\Users\UserHelper */
$users = $cms->helper('users');
$notifications = $cms->helper('notifications');
$step = null;

//build form
$form = new Formward\Form('');
$form['email'] = new Formward\Fields\Email('Email address');
$form['email']->required();
$form['email']->addValidatorFunction(
    'exists',
    function ($field) use ($users) {
        if (!$users->getByEmail($field->value())) {
            return "User not found";
        }
        return true;
    }
);
$form['email']->default($package['url.args.email']);

if ($package['url.args.token'] && $package['url.args.email']) {
    $user = $users->getByEmail($package['url.args.email']);
    if ($user) {
        if ($user['password_reset.token'] == $package['url.args.token'] && time() < $user['password_reset.time'] + 86400) {
            $notifications->printConfirmation('Password reset link accepted. Please enter a new password for your account.');
            $form['email']->disabled(true);
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
            $step = 'setpassword';
        } else {
            $notifications->printError('Reset token invalid or expired');
        }
    } else {
        $notifications->printError('User not found');
    }
}

// print/handle form
if ($form->handle()) {
    $user = $users->getByEmail($form['email']->value());
    if (!$step) {
        // first step is to try and send an email
        if (time() < $user['password_reset.time'] + 3600) {
            // rate limit reset requests
            $notifications->printWarning('You must wait at least an hour between password reset requests');
        } else {
            // save values and send email
            $user['password_reset'] = [
                'token' => bin2hex(random_bytes(16)),
                'time' => time(),
                'ip' => $_SERVER['REMOTE_ADDR']
            ];
            $user->update();
            /** @var \Digraph\Mail\MailHelper */
            $mail = $cms->helper('mail');
            $message = $mail->message();
            $message->addTo($user->email());
            $message->addTo($form['email']->value());
            $message->setSubject('Account password reset');
            /** @var \Digraph\Urls\Url */
            $tokenURL = $package->url();
            $tokenURL['args.email'] = $form['email']->value();
            $tokenURL['args.token'] = $user['password_reset.token'];
            $body = '<p>A password reset was recently requested for your account on ' . $cms->config['url.domain'] . '<p>';
            $body .= "<p><a href=\"$tokenURL\">Click here to set a new password</a></p>";
            $body .= '<p>If you did not initiate this reset, simply ignore this email and your password will not be reset. The reset link expires after 24 hours.</p>';
            $message->setBody($body);
            $mail->send($message);
            // notify of success
            $notifications->printConfirmation('An email with a password reset link has been queued for sending. Please note that delivery may take up to 30 minutes.');
        }
    } else {
        // second step is to set password and bounce to signin page
        $user->setPassword($form['password']->value());
        $user['password_reset_last'] = $user['password_reset'];
        unset($user['password_reset']);
        $user->update();
        $notifications->flashConfirmation('Password changed, you can now sign in');
        $package->redirect($cms->helper('urls')->parse('_user/signin?manager=' . $user->managerName()));
    }
} else {
    echo $form;
}
