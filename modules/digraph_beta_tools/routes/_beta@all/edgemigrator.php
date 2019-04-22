<?php
$package->noCache();
$token = $cms->helper('session')->getToken('edgemigrator');

/* execute if requested */
if (@$_GET['token'] && $cms->helper('session')->checkToken('edgemigrator', @$_GET['token'])) {
    //try to set max execution time to unlimited
    ini_set('max_execution_time', 0);
    $limit = intval(ini_get('max_execution_time'));
    $limit = $limit?$limit-2:0;
    //do migration
    $e = $cms->helper('edges');
    $search = $cms->factory()->search();
    $search->where('${digraph.parents_string} IS NOT null');
    $r = $search->execute();
    $ncount = 0;
    $ecount = 0;
    $start = time();
    foreach ($r as $n) {
        $ps = $n['digraph.parents'];
        $cid = $n['dso.id'];
        foreach ($ps as $pid) {
            if ($e->create($pid, $cid)) {
                $ecount++;
            } else {
                $cms->helper('notifications')->error('error creating edge');
            }
        }
        $ncount++;
        unset($n['digraph.parents']);
        unset($n['digraph.parents_string']);
        $n->update(true);
        if (time()-$start >= $limit) {
            break;
        }
    }
    //display results
    $cms->helper('notifications')->confirmation('Migrated '.$ncount.' nouns, totalling '.$ecount.' edges');
}

/* display current state */
$token = $cms->helper('session')->getToken('edgemigrator');
$search = $cms->factory()->search();
$search->where('${digraph.parents_string} IS NOT null');
$r = $search->execute();
if ($r) {
    echo <<<EOT
<h2>Content in need of migration</h2>
<p>
    <a class='cta-button' href='?token=$token'>Migrate batch</a>
</p>
<ul>
EOT;
    foreach ($r as $n) {
        echo "<li>".$n->url()->html()."</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nothing needs migrating.</p>";
}
