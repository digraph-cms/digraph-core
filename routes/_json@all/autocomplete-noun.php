<?php
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$base = $cms->config['url.base'];
if (substr($q, 0, strlen($base)) == $base) {
    $q = substr($q, strlen($base));
}

$results = [];

// look for exact matches
foreach ($cms->locate($q) as $n) {
    $results[$n['dso.id']] = $n;
}

// set up basic search
$search = $cms->factory()->search();
$search->limit(20);
$search->order('${dso.modified.date} desc');

// look for leading dso ID matches
$search->where('${dso.id} like :q');
runsearch($search, ['q' => "$q%"], $results);

// look for exact name/title matches
$search->where('${digraph.name} = :q OR ${digraph.title} = :q');
runsearch($search, ['q' => "$q"], $results);

// look for leading name/title matches
$search->where('${digraph.name} like :q OR ${digraph.title} like :q');
runsearch($search, ['q' => "$q%"], $results);

// look for partial name/title matches
$search->where('${digraph.name} like :q OR ${digraph.title} like :q');
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
