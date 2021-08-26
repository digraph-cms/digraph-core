<h1>Cookie authorization required</h1>
<p>
    You <em>must</em> provide the following cookie authorizations to use this page.
    You are free to continue using other parts of this site, but this page will not function without the specified cookies.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\Session\Cookies;

$form = Cookies::form(Context::thrown()->cookieTypes(), true);
if ($form->handle()) {
    Context::response()->redirect(Context::url());
} else {
    echo $form;
}
