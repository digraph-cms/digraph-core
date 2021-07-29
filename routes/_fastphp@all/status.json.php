<?php

$cms = $package->cms();
/** @var Digraph\Users\SessionHelper */
$session = $cms->helper('session');
$cacheID = 'status.json/' . md5($session->userID() . serialize($package['request.url']));

// status is empty by default
$status = [];

$cached = $session->get($cacheID);
if ($cached && time() < $cached['expires']) {
    // load cacheable bits
    $status['useractions'] = $cached['useractions'];
    $status['actionbars'] = $cached['actionbars'];
} else {
    // load user actionbar
    if ($package['url.args.useractions'] != 'false') {
        if ($cms->helper('users')->id()) {
            $status['useractions'] = $cms->helper('actions')->html('_user/signedin');
        } else {
            $status['useractions'] = $cms->helper('actions')->html('_user/guest');
        }
    }
    // load noun actionbars
    if ($package['url.args.actionbars'] != 'false') {
        $status['actionbars'] = [];
        foreach (array_filter(explode(',', $package['url.args.actionbars'])) as $n) {
            $status['actionbars'][$n] = $cms->helper('actions')->html($n, 'display');
        }
    }
}

// load flash notifications
if ($package['url.args.notifications'] != 'false') {
    $status['notifications'] = $cms->helper('notifications')->flashes();
}

// output as json
echo json_encode($status);

// cache
$session->set($cacheID, [
    'expires' => time() + ($session->userID() ? 60 : 600),
    'useractions' => $status['useractions'],
    'actionbars' => $status['actionbars']
]);
