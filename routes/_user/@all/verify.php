<?php
$package->cache_noStore();

/** @var \Digraph\Users\UserHelper */
$users = $cms->helper('users');
$user = $users->user();
/** @var \Digraph\Templates\NotificationsHelper */
$notifications = $cms->helper('notifications');

// skip out if email is already verified
if ($user['email.verified'] && !$user['email.pending']) {
    $notifications->printConfirmation('Your email address is verified');
    return;
}

// check if a token is in the url
if ($package['url.args.token']) {
    if (!$user->checkEmailToken($package['url.args.token'])) {
        // invalid or expired token
        $notifications->printError('Verification token expired or invalid');
    } else {
        // verify email
        $user['email.primary'] = $user['email.pending.address'];
        $user['email.verified'] = true;
        // save verification info to pending_last
        $user['email.pending_last'] = $user['email.pending'];
        $user['email.pending_last.verified_time'] = time();
        $user['email.pending_last.verified_ip'] = $_SERVER['REMOTE_ADDR'];
        // unset old pending
        unset($user['email.pending']);
        // update and print convfirmation
        $user->update();
        $notifications->printConfirmation('Email address verified successfully');
        return;
    }
}

// display non-verified message and option to resend
$notifications->printWarning('Your email address is not verified, please check your inbox for an email with a verification link.');
$resendLink = $cms->helper('urls')->parse('_user/verify?resend=1');

// resend if arg is set
if ($package['url.args.resend']) {
    if (time() > $user['email.pending.time'] + 3600) {
        $user->addEmail($user['email.pending.address']);
        $users->sendVerificationEmail($user);
    }
}

// display links to resend if it is expired or more than an hour old
if (time() > $user['email.pending.time'] + 86400 * 7) {
    $notifications->printNotice("Your last signup link has expired, would you like to <a href='$resendLink'>resend it?</a>");
} elseif (time() > $user['email.pending.time'] + 3600) {
    $notifications->printNotice("Can't find the email? <a href='$resendLink'>resend it</a>");
}
