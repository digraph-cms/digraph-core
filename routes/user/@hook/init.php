<?php
$package['response.cacheable'] = false;
$users = $package->cms()->helper('users');

if (!($manager = $users->manager($package['url.args.manager']))) {
    $package->error(500, 'UserManager '.$package['url.args.manager'].' not found');
    return;
}
