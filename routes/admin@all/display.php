<?php
$package->noCache();
 ?>

<ul>
<?php
$u = $cms->helper('urls');
$p = $cms->helper('permissions');
$opts = $cms->config['admin'];
ksort($opts);
foreach ($opts as $key => $url) {
    $url = $u->parse($url);
    if ($url && $p->checkUrl($url)) {
        echo "<li>".$url->html()."</li>";
    }
}
 ?>
</ul>
