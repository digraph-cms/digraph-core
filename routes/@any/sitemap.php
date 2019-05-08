<?php
$package['fields.page_name'] = $package->noun()->name();

echo "<ul>";
sitemap($package->noun(), $cms);
echo "</ul>";

function sitemap($obj, &$cms, $max=5, $depth=1, $seen=[])
{
    if ($obj) {
        if (in_array($obj['dso.id'], $seen)) {
            return '';
        }
        $seen[] = $obj['dso.id'];
        echo "<li>".$obj->url(null, [], true)->html();
        echo " <a href=\"".$obj->url('sitemap', [], true)."\">...</a>";
        $children = $obj->children(null, true);
        if ($depth < $max && $children) {
            echo "<ul>";
            foreach ($children as $child) {
                sitemap($child, $cms, $max, $depth+1, $seen);
            }
            echo "</ul>";
        }
        echo "</li>";
    }
}
