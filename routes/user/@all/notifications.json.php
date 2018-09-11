<?php
//make media file
$package->makeMediaFile('notifications.json');

//make it non-cacheable
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;

//everything is browser cacheable by default, so non-signed in users won't make
//as many ajax requests, but the ttl is shorter
if (!$this->helper('users')->username()) {
    $package['response.ttl'] = 10;
}

//pull flashes from notifications helper
echo json_encode($this->helper('notifications')->flashes());
