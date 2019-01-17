<?php
$package->noCache();
$touched = $package->noun()->invalidateCache(true, true);
echo "<p>".$cms->helper('strings')->string('clearcache')."</p>";

echo "<ul>";
foreach ($touched as $id) {
    echo "<li>";
    echo $cms->read($id)->link();
    echo "</li>";
}
echo "</ul>";
