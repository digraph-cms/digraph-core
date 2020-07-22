<?php
//very aggressive caching
$package['response.ttl'] = 1800;
$package['response.browserttl'] = 86400;

$package->makeMediaFile('linked.css');
$nouns = json_decode($package['url.args.nouns'], true);
$verbs = [$package['url.args.verb']];

array_unshift($nouns, '_');
array_unshift($verbs, '_');

foreach ($nouns as $noun) {
    foreach ($verbs as $verb) {
        echo "/* $noun $verb */\n";
        echo $cms->helper('media')->getContent('_routemedia/'.$noun.'/'.$verb.'/linked.css');
    }
}
