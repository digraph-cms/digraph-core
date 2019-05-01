<?php
$package->noCache();
$root = $package['url.args.root'];

if (!$root) {
    $roots = $cms->helper('edges')->roots();
    if ($home = $cms->read('home')) {
        array_unshift($roots, $home['dso.id']);
        $roots = array_unique($roots);
    }
    if (!$roots) {
        $cms->helper('notifications')->warning('Edge helper returned no roots, using "home" as single root');
        $roots = ['home'];
    }
    foreach ($roots as $root) {
        echo "<ul>";
        if ($root = $cms->read($root)) {
            sitemap($root, $cms);
        }
        echo "</ul>";
    }
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
        echo "<li>";
        if ($depth == 1) {
            echo "<strong>";
        }
        echo $obj->url(null, [], true)->html();
        echo " <a href=\"".$obj->url('sitemap', [], true)."\">...</a>";
        if ($depth == 1) {
            echo "</strong>";
        }
        $children = $obj->children(null, true);
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
