<?php
$package->cache_noStore();
$package->makeMediaFile('actionbar-noun.txt');
echo $cms->helper('actions')->html(
    $package['url.args.noun'],
    $package['url.args.verb']
);
