<?php
$package['response.ttl'] = $cms->config['pdf.pdf.ttl'];

//set up content
$package->binaryContent(
    $this->helper('pdf')->pdf($package->noun())
);

//setup filename
$filename = $package->noun()->name();
$filename = strtolower($filename);
$filename = preg_replace('/[^a-z0-9]+/', ' ', $filename);
$filename = trim($filename);
$filename = str_replace(' ', '_', $filename);
//set up hash
$hash = substr(md5(serialize($package['noun'])), 0, 8);
//set filename in package
$package->makeMediaFile($filename.'-'.date("Ymd").'-'.$hash.'.pdf');
