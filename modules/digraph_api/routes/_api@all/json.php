<?php
$api = $cms->helper('api');

$result = $api->call(
    $package['url.args.cmd'],
    $package['url.args.q']
);

if ($result === null) {
    $package->error(404);
}

$package->makeMediaFile('api.json');
echo json_encode($result);
