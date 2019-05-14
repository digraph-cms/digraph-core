<?php
use Digraph\Modules\digraph_core_types\Version;
use Digraph\Modules\digraph_core_types\Versioned;

$cms->helper('graph')->traverse(
    $cms->read('home')['dso.id'],
    function ($id, $depth, $pid) use ($cms) {
        $noun = $cms->read($id);
        $parent = $cms->read($pid);
        if ($noun && $parent) {
            if ($noun instanceof Version && $parent instanceof Versioned) {
                if (!$cms->helper('edges')->get($pid, $id, 'version')) {
                    echo "<div>No version relationship for ".$parent->link()." => ".$noun->link()."</div>";
                    $cms->helper('edges')->delete($pid, $id, 'normal');
                    $cms->helper('edges')->create($pid, $id, 'version');
                }
            }
        }
    }
);
