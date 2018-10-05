<?php
//make media file
$package->makeMediaFile('actionbar.json');

//return empty array for non-signed-in users
if (!$this->helper('users')->id()) {
    echo '[]';
    return;
}

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

//sort links
ksort($links);

//include object title
echo json_encode(array_values($links));
