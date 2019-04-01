<?php
$package->noCache();
$token = $cms->helper('session')->getToken('rebuildindex');

/* establish the time being used to mark this action */
if (!$package['url.args.time']) {
    $package['url.args.time'] = time();
    $package->redirect($package->url());
    return;
}

/* execute if requested */
if (@$_GET['token'] && $cms->helper('session')->checkToken('rebuildindex', @$_GET['token'])) {
    $s = $cms->helper('search');
    $search = $cms->factory()->search();
    $search->where('${digraph.lastsearchindex} is null OR ${digraph.lastsearchindex} < :time');
    $search->order('${digraph.lastsearchindex} asc');
    $r = $search->execute([":time"=>$package['url.args.time']]);
    $ncount = 0;
    $start = time();
    foreach ($r as $n) {
        $s->index($n);
        $ncount++;
        $n['digraph.lastsearchindex'] = $package['url.args.time'];
        $n->update(true);
        if (time()-$start >= 10) {
            break;
        }
    }
    //display results
    $cms->helper('notifications')->confirmation('Indexed '.$ncount.' nouns');
}

/* display current state */
$token = $cms->helper('session')->getToken('rebuildindex');
$search = $cms->factory()->search();
$search->where('${digraph.lastsearchindex} is null OR ${digraph.lastsearchindex} < :time');
$search->order('${digraph.lastsearchindex} asc');
$r = $search->execute([":time"=>$package['url.args.time']]);
if ($r) {
    echo <<<EOT
<h2>Content in need of indexing</h2>
<p>
    <a class='cta-button' href='?token=$token'>Re-index batch</a>
</p>
<p>
    Each batch will index as many nouns as it can in 10 seconds
</p>
<ul>
EOT;
    foreach ($r as $n) {
        echo "<li>".$n->url()->html()."</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nothing needs indexing.</p>";
}
