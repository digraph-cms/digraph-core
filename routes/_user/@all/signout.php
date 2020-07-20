<?php
$package['response.cacheable'] = false;
$managerName = $this->helper('users')->userManager();
$package->noCache();
$package['response.browserttl'] = 0;

//do signout bounces
if ($package['url.args.bounce']) {
    if ($cms->helper('session')->checkToken('bounce.'.$package['url.args.bounce'], $package['url.args.bounce_token'], true)) {
        $postSignoutUrl = $this->helper('urls')->parse($package['url.args.bounce']);
    }
}
if (!$postSignoutUrl) {
    $cms->helper('notifications')->flashConfirmation('You are now signed out');
    $postSignoutUrl = $this->helper('urls')->parse('_user');
}
$package->redirect($postSignoutUrl, 303);

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
