<?php
$managerName = $this->helper('users')->userManager();
$package->cache_noStore();

// determine post-signout bounce destination
/** @var \Digraph\Urls\UrlHelper */
$u = $cms->helper('urls');
$postSignoutUrl = $package->url()->getData() ?? $this->helper('urls')->parse('_user/signin');

// flash notice if not signed in
if (!$managerName) {
    $cms->helper('notifications')->flashNotice('You are not signed in');
    return;
}

//check for pre hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName . '/signout_pre.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signout_pre.php') as $file) {
    include $file['file'];
}

//do signout
$package->cms()->helper('users')->signout();

//check for post hooks
foreach ($this->helper('routing')->allHookFiles('_user', $managerName . '/signout_post.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles('_user', 'signout_post.php') as $file) {
    include $file['file'];
}

//do redirect last, in case of errors above
$cms->helper('notifications')->flashConfirmation('You are now signed out');
$package->redirect(
    $postSignoutUrl,
    303
);
