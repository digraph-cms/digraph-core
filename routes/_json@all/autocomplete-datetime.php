<?php
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$results = [];
$date = $package['url.args.date'] == 'true';
$definitive = $package['url.args.definitive'] == 'true';

// set results to query for definitive request
if ($definitive) {
    $results[] = intval($q);
}
// do parsing for regular requests
elseif ($time = strtotime($q)) {
    $results[] = $time;
}

// turn time to 0:00 for date requests
if ($date) {
    $results = array_map(
        function ($t) {
            return strtotime(date('F j, Y', $t));
        },
        $results
    );
}

// build final JSON output
$results = array_values(array_map(
    function ($n) use ($cms, $date) {
        return [
            'value' => $n,
            'label' => $date ? $cms->helper('strings')->dateHTML($n) : $cms->helper('strings')->datetimeHTML($n),
        ];
    },
    $results
));

// trim to single result for definitive results
if ($results && $definitive) {
    $results = $results[0];
}

// return json encoded
echo json_encode($results);
