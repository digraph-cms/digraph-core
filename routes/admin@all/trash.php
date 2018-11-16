<?php
$factory = $cms->factory();
$search = $factory->search();
$search->order('${dso.modified.date} desc');

$results = $search->execute([], true);

echo "<table>";
foreach ($results as $noun) {
    echo "<tr>";
    echo '<td><a href="'.$this->url('admin', 'trash-item', ['id'=>$noun['dso.id']]).'">'.$noun->name().'</a></td>';
    echo "</tr>";
}
echo "</table>";
