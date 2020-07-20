<?php
//moderately aggressive caching for non-signed-in users
$package['response.ttl'] = 10;
$package['response.browserttl'] = 10;

// much less aggressive caching for signed-in users
if ($cms->helper('users')->user()) {
    $package['response.ttl'] = 0;
    $package['response.browserttl'] = 0;
}

//make media file
$package->makeMediaFile('notifications.json');

//pull flashes from notifications helper
echo json_encode($this->helper('notifications')->flashes());
