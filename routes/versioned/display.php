<?php
if (!($version = $package->noun()->currentVersion())) {
    $cms->helper('notificiations')->warning('No current version found available');
    return;
}
