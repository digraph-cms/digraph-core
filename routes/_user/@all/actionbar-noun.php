<?php
$package->noCache();
$package['response.browserttl'] = 0;
$package->makeMediaFile('actionbar-noun.txt');
echo $cms->helper('actions')->html(
    $package['url.args.noun'],
    $package['url.args.verb']
);
