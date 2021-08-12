<?php

use DigraphCMS\Context;

$requestUrl = Context::request()->url();
$staticUrl = Context::data('300_static');
$pages = Context::data('300_pages');

?>
<h1>Multiple options</h1>
<p>The requested URL <code><?php echo htmlentities($requestUrl); ?></code> currently refers to more than one page. Please select one of the options below to continue.</p>
<ul>
    <?php
    if ($staticUrl) {
        echo "<li>" . $staticUrl->html() . "</li>";
    }
    while ($page = $pages->fetch()) {
        $url = $page->url($requestUrl->action(), $requestUrl->query(), true);
        $url->normalize();
        echo "<li>" . $url->html() . " <small>#" . $page->uuid() . "</small></li>";
    }
    ?>
</ul>