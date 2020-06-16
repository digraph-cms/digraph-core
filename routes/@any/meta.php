<?php
$package->noCache();
$noun = $package->noun();
$s = $cms->helper('strings');

$meta = [
    'id' => $noun['dso.id'],
    'name' => $noun->name(),
    'title' => $noun->title(),
    'main url' => $noun->url(),
    'created' => $s->datetime($noun['dso.created.date']) . '<br>' . $noun['dso.created.user.id'] . '<br>' . $noun['dso.created.user.ip'],
    'modified' => $s->datetime($noun['dso.modified.date']) . '<br>' . $noun['dso.modified.user.id'] . '<br>' . $noun['dso.modified.user.ip'],
];

$slugs = $cms->helper('slugs')->slugs($noun);
if (count($slugs) > 1) {
    $meta['slugs'] = implode(PHP_EOL,$slugs);
}

$files = $cms->helper('filestore')->allFiles($noun);
if ($files) {
    $meta['files'] = implode(PHP_EOL,array_map(function($e){
        return $e->metaCard();
    },$files));
}

echo "<table>";
foreach ($meta as $k => $v) {
    echo "<tr>";
    echo "<td>$k</td>";
    echo "<td>" . $v . "</td>";
    echo "</tr>";
}
echo "</table>";
