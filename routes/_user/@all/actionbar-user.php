<?php
$package->cache_noStore();
$package->makeMediaFile('actionbar-user.txt');
if ($cms->helper('users')->id()) {
    echo $cms->helper('actions')->html('_user/signedin');
}else {
    echo $cms->helper('actions')->html('_user/guest');
}
