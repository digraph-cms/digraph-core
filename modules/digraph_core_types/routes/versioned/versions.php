<h2>Version list</h2>
<?php
$versions = $package->noun()->availableVersions();

echo "<table>";
foreach ($versions as $k => $v) {
    echo "<tr>";
    echo "<td>".$v->url()->html()."</td>";
    echo "<td>";
    echo $cms->helper('strings')->datetimeHTML($v->effectiveDate());
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
