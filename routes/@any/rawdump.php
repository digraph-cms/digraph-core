<?php
use Digraph\DataObject\FieldMutator\FieldMutatorArrayInterface;

$noun = $package->noun();
$p = new Flatrr\Config\Config($noun->get());

echo "<pre>".htmlentities($p->yaml())."</pre>";

echo "<h3>Publication state</h3>";
echo "<ul><li>".($noun->isPublished()?'published':'unpublished')."</li></ul>";

if ($parents = $noun->parents()) {
    echo "<h3>Parents</h3>";
    echo "<ul>";
    foreach ($parents as $o) {
        echo "<li>".$o->url()->html()."</li>";
    }
    echo "</ul>";
}

if ($children = $noun->children()) {
    echo "<h3>Children</h3>";
    echo "<ul>";
    foreach ($children as $o) {
        echo "<li>".$o->url()->html()."</li>";
    }
    echo "</ul>";
}
