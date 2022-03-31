<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\URL\URL;

$top = Breadcrumb::top();
$top->setName('Disambiguation page');
Breadcrumb::top($top);
Breadcrumb::parent(new URL('/'));

$requestUrl = Context::request()->originalUrl();
$staticUrl = Context::data('300_static');
$pages = Context::data('300_pages');

?>
<h1>Multiple options</h1>
<p>The requested URL <code><?php echo htmlentities($requestUrl); ?></code> may refer to more than one piece of content, so this disambiguation page has been generated to display all possible options.</p>
<ul class='error-options-300'>
    <?php
    $urls = [];
    if ($staticUrl) {
        echo "<li>" . $staticUrl->html() . "</li>";
        $urls[] = $staticUrl;
    }
    foreach ($pages as $page) {
        if (!Router::pageRouteExists($page, $requestUrl->action())) {
            continue;
        }
        $urls[] = $url = $page->url($requestUrl->action(), $requestUrl->query());
        $url->normalize();
        echo "<li>" . $url->html() . " <small class='page-uuid'>" . $page->uuid() . "</small></li>";
    }
    /**
     * If only one URL was found, redirect straight to it. This is a rare
     * situation, and it's better to do this check here when there's already a
     * 300 error potential than to complicate/slow every single pageview doing
     * these kinds of checks there.
     */
    if (count($urls) == 1) {
        throw new RedirectException(reset($urls));
    }
    ?>
</ul>

<?php
Router::include('/~error/trace.php');
