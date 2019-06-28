<?php
if (!$package->noun()->isViewable()) {
    //deny access for those with no access
    $package->error(403);
}
//always make browser-side TTL 0
$package['response.browserttl'] = 0;
