<?php
$search = $cms->helper('search');


// $search->index($cms->read('home'));
// $search->index($cms->read('10j6fvt0'));

foreach ($search->search($package['url.args.q']) as $n) {
    if (!$n) {
        echo "<div>[not found]</div>";
    } else {
        echo "<div>".$n->link()."</div>";
    }
}
