<?php
$package['response.ttl'] = $cms->config['pdf.pdfbook.ttl'];

//set up content
$package->binaryContent(
    $this->helper('pdf')->pdfBook($package->noun())
);

//setup filename
$filename = $package->noun()->name();
$filename = strtolower($filename);
$filename = preg_replace('/[^a-z0-9]+/', ' ', $filename);
$filename = trim($filename);
$filename = str_replace(' ', '_', $filename);
//set filename in package
$package->makeMediaFile($filename.'-book-'.date("Ymd").'.pdf');
