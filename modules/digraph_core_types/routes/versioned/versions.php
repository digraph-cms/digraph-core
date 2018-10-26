<h2>Version list</h2>
<?php
$versions = $package->noun()->availableVersions();

echo "<table>";
foreach ($versions as $k => $v) {
    echo "<tr>";
    echo "<td>".$v->url()->html()."</td>";
    echo "<td>";
    if (!$v['digraph.published.force'] && $v['digraph.published.start']) {
        $date = $v['digraph.published.start'];
    } else {
        $date = $v['dso.created.date'].'-'.$v['dso.id'];
    }
    echo $cms->helper('strings')->datetimeHTML($date);
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
