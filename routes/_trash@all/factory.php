<?php
$package->noCache();
$factory = $cms->factory($this->arg('factory'));
$search = $factory->search();
$search->order('${dso.modified.date} desc');

$results = $search->execute([], true);

echo "<table>";
foreach ($results as $noun) {
    echo "<tr>";
    echo '<td><a href="'.$this->url('_trash', 'item', ['factory'=>$this->arg('factory'),'id'=>$noun['dso.id']]).'">'.$noun->name().'</a></td>';
    echo "</tr>";
}
echo "</table>";
