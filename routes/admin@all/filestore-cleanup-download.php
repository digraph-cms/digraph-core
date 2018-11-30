<?php
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;

$fs = $this->helper('filestore');
$file = $fs->getByHash($package['url.args.f']);

if (!$file) {
    $package->error(404, 'File with that hash not found in filestore');
    return;
}

$package->makeMediaFile($file['name']);
$package['response.readfile'] = $file['file'];
$package['response.disposition'] = 'attachment';
