<?php
$package['response.ttl'] = 0;
$package['response.cacheable'] = false;
$root = $package['url.args.root'];

if (!$root) {
    echo "<ul>";
    $search = $this->cms()->factory()->search();
    $search->where('${digraph.parents.0} is null');
    $search->order('${digraph.modified.date} desc');
    foreach ($search->execute() as $root) {
        sitemap($root, $cms);
    }
    echo "</ul>";
} else {
    $root = $this->cms()->read($root);
    if (!$root) {
        $package->error(404);
        return;
    }
    $package['fields.page_title'] = $root->name();
    echo "<ul>";
    sitemap($root, $cms);
    echo "</ul>";
}

function sitemap($obj, &$cms, $max=5, $depth=1)
{
    if ($obj) {
        echo "<li>".$obj->url()->html();
        echo " <a href=\"".$obj->url('sitemap')."\">...</a>";
        $children = $obj->children();
        if ($depth < $max && $children) {
            echo "<ul>";
            foreach ($children as $child) {
                sitemap($child, $cms, $max, $depth+1);
            }
            echo "</ul>";
        }
        echo "</li>";
    }
}
