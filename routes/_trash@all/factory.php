<?php
$package->cache_noStore();
$token = $cms->helper('session')->getToken('emptytrash');

$factory = $cms->factory($this->arg('factory'));
$search = $factory->search();
$search->order('${dso.modified.date} desc');

/* execute if requested */
if (@$_GET['token'] && $cms->helper('session')->checkToken('emptytrash', @$_GET['token'])) {
    //try to set max execution time to unlimited
    ini_set('max_execution_time', 0);
    foreach ($search->execute([], true) as $item) {
        $item->delete(true);
    }
}

echo "<p><a class='cta-button' href='?factory=".$this->arg('factory')."&token=$token'>Empty trash</a></p>";

$results = $search->execute([], true);

echo "<table>";
foreach ($results as $noun) {
    echo "<tr>";
    echo '<td><a href="'.$this->url('_trash', 'item', ['factory'=>$this->arg('factory'),'id'=>$noun['dso.id']]).'">'.$noun->name().'</a></td>';
    echo "</tr>";
}
echo "</table>";
