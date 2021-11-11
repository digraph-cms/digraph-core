<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\UI\ActionMenu;

/** @var \DigraphCMS\URL\URL */
$url = Context::fields()['url'] ?? Context::url();
$page = $url->page();

if (!$url->explicitlyStaticRoute() && $page = $url->page()) {
    $actions = Router::pageActions($page);
} else {
    $actions = Router::staticActions($url->route());
}

echo "<ul class='toc'>";
foreach ($actions as $url) {
    echo "<li>";
    echo $url->html([], !Context::fields()['url']);
    echo "</li>";
}
echo "</ul>";
