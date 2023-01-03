<h1>Cookie authorization required</h1>
<p>
    You must provide the following additional cookie authorizations to use this page.
    You are free to go back and continue using other parts of this site, but this page will not function without the specified cookies.
</p>
<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\CookieRequiredError;
use DigraphCMS\Session\Cookies;

/** @var CookieRequiredError */
$thrown = Context::thrown();

echo $form = Cookies::form($thrown->cookieTypes(), true, true);
if ($form->ready()) {
    throw new RefreshException();
}

Router::include('/~error/trace.php');
