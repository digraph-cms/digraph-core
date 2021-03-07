<?php
//f arg is required, and indicates either a filename or a uniqid
if (!$package->noun()::FILESTORE || !($f = $package['url.args.f'])) {
    $package->error(404, 'file not specified or filestore not enabled for this type');
    return;
}

//ask filestore for matching files
$fs = $cms->helper('filestore');
$noun = $package->noun();
if (!($files = $fs->get($noun, $f))) {
    $package->error(404);
    return;
}

//finally if everything is good, output the file
/** @var Digraph\FileStore\FileStoreFile */
$f = array_pop($files);

//if image handler can do this file, use it
$i = $cms->helper('image');
$ext = preg_replace('/.+\./', '', $f->name());
if ($i->supports($ext)) {
    $url = $f->imageUrl($package['url.args.a']);
} else {
    // otherwise use regular file URL
    $url = $f->url();
}

//set up redirect to asset file
$package->redirect($url, 301);
