<?php
if (!($object = $this->argObject('object'))) {
    $package->error(404, 'object must be specified');
    return;
}

//make media file
$package->makeMediaFile('user-actionbar.json');

//return empty array for non-signed-in users
if (!$this->helper('users')->username()) {
    echo '[]';
}

//not cacheable for signed-in users, so that actions are unique to each user
$package['response.cacheable'] = false;
$package['response.ttl'] = 30;
$output = [
    'user' => $this->helper('users')->username(),
    'object' => $object['dso.id'],
    'actions' => []
];

echo json_encode($output);
