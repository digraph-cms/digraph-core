<?php
$versions = $package->noun()->availableVersions();

echo "<form action='".$this->url($package['noun.dso.id'], 'version-diff', [])."' method='get'>";
echo "<table style='width:100%;'>";
foreach ($versions as $k => $v) {
    echo "<tr>";
    echo "<td><input type='checkbox' class='compare-selector-cb' name='".$v['dso.id']."' value=''></td>";
    echo "<td>".$v->url()->html()."</td>";
    echo "<td>";
    echo $cms->helper('strings')->datetimeHTML($v->effectiveDate());
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<div class='fixed-controls'>";
echo "<input type='submit'></div>";
echo "</form>";
