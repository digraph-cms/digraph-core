<?php
$package->cache_private();
$package['response.ttl'] = 30;
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$base = $cms->config['url.base'];
if (substr($q, 0, strlen($base)) == $base) {
    $q = substr($q, strlen($base));
}

$types = array_filter(explode(',', $package['url.args.types']));

$results = false;

// look for exact matches
if ($n = $cms->read($q)) {
    if (!$types || in_array($n['dso.type'], $types)) {
        $results = [
            'value' => $n['dso.id'],
            'label' => $n->name(),
            'url' => $n->url()->__toString(),
        ];
    }
}

// build final JSON output
echo json_encode($results);
