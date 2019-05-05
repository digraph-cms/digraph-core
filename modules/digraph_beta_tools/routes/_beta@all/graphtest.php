<?php
$g = $cms->helper('graph');

foreach ($g->children('3isc3b3j', 2) as $n) {
    echo "<div>".$n->link()."</div>";
}

// var_dump($g->route('c6omxxru', 'efnje4xu'));

// var_dump(
//     $g->traverse(
//         'c6omxxru',
//         function ($id, $depth, $parent) use ($cms) {
//             $n = $cms->read($id);
//             if ($n) {
//                 echo "<div>$depth: ".$n->link().", $parent</div>";
//             }
//         }
//     )
// );
