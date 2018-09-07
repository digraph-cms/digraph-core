<?php
//make media file
$package->makeMediaFile('user-notifications.json');

//return empty array for non-signed-in users
if (!$this->helper('users')->username()) {
    echo '[]';
}

//not cacheable for signed-in users, so that actions are unique to each user
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;
echo json_encode($this->helper('notifications')->flashes());
