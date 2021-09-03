<h1>Cookie authorization required</h1>
<p>
    You must provide the following additional cookie authorizations to use this page.
    You are free to go back and continue using other parts of this site, but this page will not function without the specified cookies.
</p>
<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Session\Cookies;

$form = Cookies::form(Context::thrown()->cookieTypes(), true, true);
if ($form->handle()) {
    Context::response()->redirect(Context::url());
} else {
    echo $form;
}

Router::include('/~error/trace.php');
