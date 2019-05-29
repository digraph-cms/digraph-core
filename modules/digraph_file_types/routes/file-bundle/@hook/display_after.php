<?php
$noun = $package->noun();
$fs = $cms->helper('filestore');
$files = $fs->list($noun, $noun::FILESTORE_PATH);
if (!$files) {
    return;
}

if ($package->noun()['file-bundle.gallery']) {
    echo $cms->helper('filters')->filter('bbcode_advanced')->filter(
        '[gallery /]'
    );
} else {
    foreach ($files as $f) {
        echo $f->metacard();
    }
}
