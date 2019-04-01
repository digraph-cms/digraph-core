<?php
$package->noCache();
$s = $cms->helper('slugs');
$p = $cms->helper('paginator');
$count = $s->count();

echo "<p>This site currently has $count custom URLs. The most recently added/updated ones are listed first and take precedence when determining the preferred URL of a page.</p>";

echo $p->paginate(
    $count,
    $package,
    'page',
    20,
    function ($start, $end) use ($s,$cms) {
        $out = '';
        $out .= "<table>";
        $out .= "<tr><th>URL</th><th>Noun</th></tr>";
        foreach ($s->list(20, $start-1) as $slug) {
            $url = $slug['slug_url'];
            $noun = $cms->read($slug['slug_noun']);
            $nlink = $noun?$noun->link():'[noun not found]';
            $out .= "<tr>";
            $out .= "<td>$url</td>";
            $out .= "<td>$nlink</td>";
            $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);
