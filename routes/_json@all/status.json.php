<?php
$package->cache_noStore();
$package->makeMediaFile('status.json');
$cms = $package->cms();

// status is empty by default
$status = [];

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

// load flash notifications
if ($package['url.args.notifications'] != 'false') {
    $status['notifications'] = $cms->helper('notifications')->flashes();
}

// output as json
echo json_encode($status);
