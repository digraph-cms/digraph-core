<?php
$noun = $package->noun();
$fs = $cms->helper('filestore');
$files = $fs->list($noun, $noun::FILESTORE_PATH);
if (!$files) {
    $cms->helper('notifications')->error(
        $cms->helper('strings')->string('file.notifications.nofile')
    );
    return;
}
foreach ($files as $f) {
    echo $f->metacard();
}
