<?php
$package->noCache();
$s = $cms->helper('slugs');
$count = $s->count();
$page = intval($package['url.args.page']);
$perpage = 100;
$pages = ceil($count/$perpage);
$pages = $pages?$pages:1;
$page = ($page>0)?$page:1;
$page = ($page<=$pages)?$page:$pages;
$offset = ($page-1)*$perpage;

echo "<p>This site currently has $count custom URLs. The most recently added/updated ones are listed first and take precedence when determining the preferred URL of a page.</p>";

if ($pages > 1) {
    echo "<p><em>Page $page of $pages</em> | ";
    $plist = [];
    for ($i=1; $i <= $pages; $i++) {
        if ($i == $page) {
            $plist[] = "<strong>$i</strong>";
        } else {
            $plist[] = "<a href='".$this->url('_slugs', 'list', ['page'=>$i])."'>$i</a>";
        }
    }
    echo implode(' ', $plist)."</p>";
}

$slugs = $s->list($perpage, $offset);
echo "<table>";
echo "<tr><th>URL</th><th>Noun</th></tr>";
foreach ($slugs as $slug) {
    $url = $slug['slug_url'];
    $noun = $cms->read($slug['slug_noun']);
    $nlink = $noun?$noun->link():'[noun not found]';
    echo "<tr>";
    echo "<td>$url</td>";
    echo "<td>$nlink</td>";
    echo "</tr>";
}
echo "</table>";
