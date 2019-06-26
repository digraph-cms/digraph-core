<?php
$package->noCache();
$token = $cms->helper('session')->getToken('rebuildslugs');

/* execute if requested */
if (@$_GET['token'] && $cms->helper('session')->checkToken('rebuildslugs', @$_GET['token'])) {
    ini_set('max_execution_time', 0);
    $g = $cms->helper('graph');
    $s = $cms->helper('slugs');
    $ncount = 0;
    $g->traverse(
        $cms->read('home')['dso.id'],
        function ($id) use (&$ncount,$cms,$s) {
            if ($noun = $cms->read($id)) {
                if ($pattern = $noun['digraph.slugpattern']) {
                    $s->createFromPattern($pattern,$noun);
                    $ncount++;
                }
            }
        }
    );
    //display results
    $cms->helper('notifications')->confirmation('Rebuilt slugs for '.$ncount.' nouns');
}

/* display current state */
$token = $cms->helper('session')->getToken('rebuildslugs');
    echo <<<EOT
<p>
    <a class='cta-button' href='?token=$token'>Rebuild slugs for entire site</a>
</p>
EOT;
