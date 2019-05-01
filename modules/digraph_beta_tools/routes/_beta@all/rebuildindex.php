<?php
$package->noCache();
$token = $cms->helper('session')->getToken('rebuildindex');

/* execute if requested */
if (@$_GET['token'] && $cms->helper('session')->checkToken('rebuildindex', @$_GET['token'])) {
    ini_set('max_execution_time', 0);
    $s = $cms->helper('search');
    $search = $cms->factory()->search();
    $r = $search->execute();
    $ncount = 0;
    foreach ($r as $n) {
        $s->index($n);
        $ncount++;
    }
    //display results
    $cms->helper('notifications')->confirmation('Indexed '.$ncount.' nouns');
}

/* display current state */
$token = $cms->helper('session')->getToken('rebuildindex');
    echo <<<EOT
<p>
    <a class='cta-button' href='?token=$token'>Re-index site</a>
</p>
EOT;
