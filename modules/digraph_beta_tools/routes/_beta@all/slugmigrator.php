<?php
$package->noCache();
$token = $cms->helper('session')->getToken('slugmigrator');

/* execute if requested */
if (@$_GET['token'] && $cms->helper('session')->checkToken('slugmigrator', @$_GET['token'])) {
    $s = $cms->helper('slugs');
    $search = $cms->factory()->search();
    $search->where('${digraph.slug} IS NOT null');
    $r = $search->execute();
    $ncount = 0;
    $scount = 0;
    $start = time();
    foreach ($r as $n) {
        if ($s->create($n['digraph.slug'], $n['dso.id'], true)) {
            $scount++;
        } else {
            $cms->helper('notifications')->error('error creating slug');
        }
        $ncount++;
        unset($n['digraph.slug']);
        $n->update(true);
        if (time()-$start >= 10) {
            break;
        }
    }
    //display results
    $cms->helper('notifications')->confirmation('Migrated '.$ncount.' nouns, created '.$scount.' slugs');
}

/* display current state */
$token = $cms->helper('session')->getToken('slugmigrator');
$search = $cms->factory()->search();
$search->where('${digraph.slug} IS NOT null');
$r = $search->execute();
if ($r) {
    echo <<<EOT
<h2>Content in need of migration</h2>
<p>
    <a class='cta-button' href='?token=$token'>Migrate batch</a>
</p>
<p>
    Each batch will migrate as many nouns as it can in 10 seconds
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
