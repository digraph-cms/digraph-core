<?php
//make media file
$package->makeMediaFile('notifications.json');

//make it non-cacheable
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;

//everything is browser cacheable by default, so non-signed in users won't make
//quite as many ajax requests, but the ttl is still quite short
if (!$this->helper('users')->id()) {
    $package['response.ttl'] = 30;
}

//pull flashes from notifications helper
echo json_encode($this->helper('notifications')->flashes());
