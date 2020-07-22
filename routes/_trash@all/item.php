<?php
use Digraph\DataObject\FieldMutator\FieldMutatorArrayInterface;

$package->cache_noStore();

$factory = $cms->factory($this->arg('factory'));
$search = $factory->search();
$search->order('${dso.modified.date} desc');
$search->where('${dso.id} = :id');

if (!($result = $search->execute(['id'=>$this->arg('id')], true))) {
    $package->error(404);
    return;
}

$noun = array_pop($result);
$p = new Flatrr\Config\Config($noun->get());

echo "<pre>".htmlentities($p->yaml())."</pre>";

if ($children = $noun->children()) {
    echo "<h3>Children</h3>";
    echo "<ul>";
    foreach ($children as $o) {
        if ($o) {
            echo "<li>".$o->url()->html()."</li>";
        } else {
            echo "<li>[missing or deleted]</li>";
        }
    }
    echo "</ul>";
}
