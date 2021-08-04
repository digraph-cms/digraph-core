<?php

use DigraphCMS\Content\FilesystemRouter;
use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Route;
use DigraphCMS\URL\URL;

$rUrl = Route::request()->url();
$action = $rUrl->action();
$route = $rUrl->route();
$pages = Pages::getAll($route);

?>
<h1>Multiple options</h1>
<p>The requested URL <code><?php echo htmlentities($rUrl); ?></code> currently refers to more than one page. Please select one of the options below to continue.</p>
<ul>
    <?php
    while ($page = $pages->fetch()) {
        $url = $page->url($action, $rUrl->query(), true);
        $url->normalize();
        echo "<li>" . $url->html() . "</li>";
    }
    if (FilesystemRouter::staticRouteExists($route, $action)) {
        $url = new URL("/~$route/$action.html");
        $url->query($rUrl->query());
        $url->normalize();
        echo "<li>" . $url->html() . "</li>";
    }
    ?>
</ul>