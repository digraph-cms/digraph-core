<ul>
<?php
$actions = $cms->helper('actions')->other('_slugs');
foreach ($actions as $url) {
    if ($url = $cms->helper('urls')->parse($url)) {
        echo "<li>".$url->html()."</li>";
    }
}
 ?>
</ul>
