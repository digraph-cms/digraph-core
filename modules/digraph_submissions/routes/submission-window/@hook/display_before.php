<?php
if ($cms->helper('users')->user()) {
    //no cache for signed-in users
    $package->noCache();
} else {
    //notice for non-signed-in users
    $url = $cms->helper('users')->signinUrl($package);
    $cms->helper("notifications")
        ->notice('You are not signed in. If you would like to create a submission or manage one you already created, <a href="'.$url.'">please sign in.</a>');
}
//always make browser-side TTL 0
$package['response.browserttl'] = 0;
