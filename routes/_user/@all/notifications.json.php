<?php
//moderately aggressive caching
$package['response.ttl'] = 60;
$package['response.browserttl'] = 60;

// much less aggressive caching for signed-in users
if ($cms->helper('users')->user()) {
    $package['response.ttl'] = 5;
    $package['response.browserttl'] = 5;
}

//make media file
$package->makeMediaFile('notifications.json');

//make it non-cacheable
$package->noCache();

//pull flashes from notifications helper
echo json_encode($this->helper('notifications')->flashes());
