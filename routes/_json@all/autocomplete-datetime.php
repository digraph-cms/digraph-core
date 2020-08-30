<?php
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$results = [];

// set results to query for definitive request
if ($package['url.args.definitive'] == 'true') {
    $results[] = intval($q);
}
// do parsing for regular requests
elseif ($time = strtotime($q)) {
    $results[] = $time;
}

// build final JSON output
$results = array_values(array_map(
    function ($n) use ($cms) {
        return [
            'value' => $n,
            'label' => $cms->helper('strings')->datetimeHTML($n),
        ];
    },
    $results
));
if ($results && $package['url.args.definitive'] == 'true') {
    $results = $results[0];
}
echo json_encode($results);
