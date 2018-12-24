<?php
$search = $cms->factory()->search();
$search->where('${dso.type} = :type');
$areas = $search->execute(['type'=>'blockarea']);

echo $cms->helper('urls')->parse('admin/add?type=blockarea')->html();

echo "<ul>";
foreach ($areas as $area) {
    echo "<li>".$area->link()."</li>";
}
echo "</ul>";
