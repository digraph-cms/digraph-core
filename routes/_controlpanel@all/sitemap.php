<?php
$package->cache_noStore();
$roots = $cms->helper('edges')->roots(true);
if ($home = $cms->read('home')) {
    array_unshift($roots, $home['dso.id']);
    $roots = array_unique($roots);
}
if (!$roots) {
    $cms->helper('notifications')->warning('Edge helper returned no roots, using "home" as single root');
    $roots = [$cms->read("home")];
}
foreach ($roots as $root) {
    echo "<ul>";
    if ($root = $cms->read($root)) {
        sitemap($root, $cms);
    }
    echo "</ul>";
}

function sitemap($obj, &$cms, $max=5, $depth=1, $seen=[])
{
    if ($obj) {
        if (in_array($obj['dso.id'], $seen)) {
            return '';
        }
        $seen[] = $obj['dso.id'];
        echo "<li>";
        if ($depth == 1) {
            echo "<strong>";
        }
        echo $obj->url(null, [], true)->html();
        echo " <a href=\"".$obj->url('sitemap', [], true)."\">...</a>";
        if ($depth == 1) {
            echo "</strong>";
        }
        $children = $cms->helper('edges')->children($obj['dso.id']);
        if ($depth < $max && $children) {
            echo "<ul>";
            foreach ($children as $child) {
                if ($child = $cms->read($child->end())) {
                    sitemap($child, $cms, $max, $depth+1, $seen);
                }
            }
            echo "</ul>";
        }
        echo "</li>";
    }
}
