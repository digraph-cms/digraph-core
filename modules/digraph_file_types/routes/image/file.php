<?php
/**
 * image needs its own file verb handler so that it can preprocess images. By
 * default this even means stripping metadata from images before sending them
 * to the browser.
 */

//f arg is required, and indicates either a filename or a uniqid
if (!$package->noun()::FILESTORE || !($f = $package['url.args.f'])) {
    $package->error(404, 'file not found or filestore not enabled for this type');
    return;
}

//ask filestore for matching files
$fs = $cms->helper('filestore');
if (!($files = $fs->get($package->noun(), $f))) {
    $package->error(404);
    return;
}

//if more than one file is returned, generate a 300 page with uniqid links
if (count($files) > 1) {
    $noun = $package->noun();
    $s = $cms->helper('strings');
    $package->error(300, 'Multiple files match');
    $package['response.300'] = [];
    foreach ($files as $f) {
        $package->push('response.300', [
            'link' => $noun->link(
                $f->name().' uploaded '.$s->datetimeHTML($f->time()),//link text
                'file',//link verb
                ['f'=>$f->uniqid()],//use file uniqid
                true//canonical URL
            )
        ]);
    }
    return;
}

//finally if everything is good, output the file
$f = array_pop($files);
$fs->output($package, $f);
