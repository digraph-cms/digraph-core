<?php
//make media file
$package->makeMediaFile('actionbar.json');

//build list of links
$links = $package->cms()->helper('actions')->get($package['url.args.id']);

//map over links to construct HTML
$links = array_map(
    function ($e) use ($package) {
        return $package->cms()->helper('urls')->parse($e);
    },
    $links
);
$links[] = null;
$links = array_filter(
    $links,
    function ($e) {
        return $e;
    }
);
$links = array_map(
    function ($e) {
        return $e->html()->string();
    },
    $links
);

//set up addables and addable_url if object exists
$addable = [];
$type = null;
$addable_url = null;
$title = $cms->helper('strings')->string('actionbar.title.default');
if ($object = $package->cms()->read($package['url.args.id'])) {
    $type = $object['dso.type'];
    $addable = $package->cms()->helper('actions')->addable($object['dso.type']);
    $addable_url = $object->url('add', [], true)->string();
    $title = $object->name();
} elseif ($package['url.args.id'] == '_user/guest') {
    $title = $cms->helper('strings')->string('actionbar.title.guest');
} elseif ($package['url.args.id'] == '_user/signedin') {
    $title = $cms->helper('users')->user()->name();
}

//include object title
echo json_encode([
    'links' => array_values($links),
    'addable' => $addable,
    'addable_url' => $addable_url,
    'type' => $type,
    'title' => $title
]);
