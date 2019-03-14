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

//if more than one file is returned, generate a 300 page with uniqid links
if (count($files) > 1) {
    $s = $cms->helper('strings');
    $package->error(300, 'Multiple files match');
    $package['response.300'] = [];
    foreach ($files as $f) {
        $args = $package['url.args'];
        $args['f'] = $f->uniqid();//use file's uniqid instead of filename
        $package->push('response.300', [
            'link' => $noun->link(
                $f->name().' uploaded '.$s->datetimeHTML($f->time()),//link text
                'gallery-file',//link verb
                $args,//args with uniqid
                true//canonical URL
            )
        ]);
    }
    return;
}

//finally if everything is good, output the file
$f = array_pop($files);

$package['fields.page_title'] = $f->name();

$t = $cms->helper('templates');
echo $t->render('pages/gallery-file.twig', ['file'=>$f,'exif'=>$f->exif()]);
