<?php
$package['response.cacheable'] = false;
$managerName = $this->helper('users')->userManager();
$package->redirect($this->helper('urls')->parse('user/signin')->string());

if (!$managerName) {
    $cms->helper('notifications')->flashNotice('You are not signed in');
    return;
}

//check for pre hooks
foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signout_pre.php') as $file) {
    if (include($file['file']) === false) {
        return;
    }
}

//do signout
$package->cms()->helper('users')->signout();

//check for post hooks
foreach ($this->helper('routing')->allHookFiles('user', $managerName.'/signout_post.php') as $file) {
    if (include($file['file']) === false) {
        return;
    }
}

$cms->helper('notifications')->flashConfirmation('You are now signed out');
