<?php
$package->cache_private();
$package['response.ttl'] = 30;
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$definitive = $package['url.args._definitive'] == 'true';

// token arg must exist
if (!$package['url.args._token']) {
    $package->error(404);
    return;
}

// load config from session
$session = $cms->helper('session');
$config = $session->get($package['url.args._token']);
$types = $config['types'];
$fields = $config['fields'];
$allowAdding = $config['allowAdding'];

// config must exist
if (!$config) {
    $package->error(404);
    return;
}

// find results
$search = $cms->factory()->search();
$where = [];
if ($types) {
    $where[] = '${dso.type} in ("' . implode('","', $types) . '")';
}
$likes = [];
foreach ($fields as $field) {
    $likes[] = '${' . $field . '} LIKE :q';
}
$where[] = '(' . implode(' OR ', $likes) . ')';
$where = implode(' AND ', $where);
$search->where($where);
$search->order('${dso.modified.date} DESC');
$search->limit(10);

// build results from returned nouns
$results = [];
$exactMatch = false;
foreach ($search->execute(['q' => "%$q%"]) as $n) {
    foreach ($fields as $field) {
        $value = strip_tags($n[$field]);
        $score = strlen($q) / strlen($value);
        if ($score == 1) {
            $exactMatch = true;
        }
        if (stristr($value, $q)) {
            $results[] = [
                'value' => $value,
                'label' => preg_replace("/" . preg_quote($q) . "/i", "<strong>$0</strong>", $value),
                'score' => $score,
            ];
        }
    }
}
if (!$exactMatch && $allowAdding) {
    $results[] = [
        'value' => $q,
        'label' => "<strong>$q</strong>",
        'score' => 1,
    ];
}
usort($results, function ($a, $b) {
    if ($a['score'] > $b['score']) {
        return -1;
    } elseif ($a['score'] < $b['score']) {
        return 1;
    } else {
        return 0;
    }
});

// trim to single result for definitive results
if ($results && $definitive) {
    $results = $results[0];
}

// return json encoded
echo json_encode($results);
