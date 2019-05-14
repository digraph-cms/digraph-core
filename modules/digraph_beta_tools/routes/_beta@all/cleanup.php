<?php
$package->noCache();
$token = $cms->helper('session')->getToken('betacleanup');

$oldkeys = [
    'digraph.noparent',
    'digraph.lastsearchindex',
    'digraph.published'
];

/* execute if requested */
if (@$_GET['token'] && $cms->helper('session')->checkToken('betacleanup', @$_GET['token'])) {
    ini_set('max_execution_time', 0);
    $edges = $cms->helper('edges');
    $search = $cms->factory()->search();
    $r = $search->execute();
    $ncount = 0;
    $kcount = 0;
    foreach ($r as $n) {
        $cleaned = 0;
        foreach ($oldkeys as $k) {
            if (isset($n[$k])) {
                unset($n[$k]);
                $cleaned++;
            }
        }
        if ($cleaned && $n->update(true)) {
            $kcount += $cleaned;
            $ncount++;
        }
        $edges->updateRootTracking($n['dso.id']);
    }
    //display results
    $cms->helper('notifications')->confirmation('Cleaned up '.$kcount.' keys in '.$ncount.' nouns');
}

/* display current state */
$token = $cms->helper('session')->getToken('betacleanup');
    echo <<<EOT
<p>
    <a class='cta-button' href='?token=$token'>Clean up old data in nouns</a>
</p>
EOT;
