<?php
$package->cache_private();
$package['response.ttl'] = 30;
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$base = $cms->config['url.base'];
if (substr($q, 0, strlen($base)) == $base) {
    $q = substr($q, strlen($base));
}

$results = [];
$where = '';

// get type limits from args
if ($types = array_filter(explode(',', $package['url.args.types']))) {
    $where = '${dso.type} in (' . implode(',', array_map(
        function ($t) {
            $t = preg_replace('/[^a-z\-]/', '', $t);
            return '"' . $t . '"';
        },
        $types
    )) . ')';
}

// set up where clause
if ($where) {
    $where = ' AND (' . $where . ')';
}

// look for exact matches
foreach ($cms->locate($q) as $n) {
    if (in_array($n['dso.type'], $types)) {
        $results[$n['dso.id']] = $n;
    }
}

// set up basic search
$search = $cms->factory()->search();
$search->limit(20);
$search->order('${dso.modified.date} desc');

// look for leading dso ID matches
$search->where('(${dso.id} like :q)' . $where);
runsearch($search, ['q' => "$q%"], $results);

// look for exact name/title matches
$search->where('(${digraph.name} = :q OR ${digraph.title} = :q)' . $where);
runsearch($search, ['q' => "$q"], $results);

// look for leading name/title matches
$search->where('(${digraph.name} like :q OR ${digraph.title} like :q)' . $where);
runsearch($search, ['q' => "$q%"], $results);

// look for partial name/title matches
$search->where('(${digraph.name} like :q OR ${digraph.title} like :q)' . $where);
runsearch($search, ['q' => "%$q%"], $results);

// build final JSON output
echo json_encode(array_values(array_map(
    function ($n) {
        return [
            'value' => $n['dso.id'],
            'label' => $n->name(),
            'url' => $n->url()->__toString(),
        ];
    },
    $results
)));

// function for adding to results
function runSearch($search, $args, &$results)
{
    foreach ($search->execute($args) as $n) {
        if (!isset($results[$n['dso.id']])) {
            $results[$n['dso.id']] = $n;
        }
    }
}
