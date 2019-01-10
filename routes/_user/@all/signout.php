<?php
$package['response.cacheable'] = false;
$managerName = $this->helper('users')->userManager();
$package->redirect($this->helper('urls')->parse('_user/signin')->string());

if (!$managerName) {
    $cms->helper('notifications')->flashNotice('You are not signed in');
    return;
}

//check for pre hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signout_pre.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signout_pre.php') as $file) {
    include $file['file'];
}

//do signout
$package->cms()->helper('users')->signout();

//check for post hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName.'/signout_post.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signout_post.php') as $file) {
    include $file['file'];
}

$cms->helper('notifications')->flashConfirmation('You are now signed out');