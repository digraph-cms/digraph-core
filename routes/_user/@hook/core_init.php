<?php
$package->cache_noStore();

$users = $package->cms()->helper('users');

$managerName = $package['url.args.manager']?$package['url.args.manager']:$cms->config['users.defaultmanager'];
if (!($manager = $users->manager($managerName))) {
    $package->error(500, 'UserManager '.$managerName.' not found');
    return;
}
