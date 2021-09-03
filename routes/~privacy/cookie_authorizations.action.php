<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

Context::response()->private(true);

?>
<h1>Current cookie authorizations</h1>
<p>
    The current browser session has authorized the following cookies to be stored on this computer.
    <?php
    if (Cookies::get('system', 'cookierules')) {
        if ($expiration = Cookies::expiration('system', 'cookierules')) {
            echo "These selections will automatically expire after $expiration, but any already-set cookies may persist longer than that.";
        } else {
            echo "These selections will automatically expire when you close your  browser, but any already-set cookies may persist longer than that.";
        }
    }
    ?>
</p>
<p>
    You can also use the form below to alter your cookie selections.
    Removing authorizations does not delete existing cookies, but will prevent them from being updated.
    If you would like to delete existing cookies, you can always view and delete any cookies using the <a href="<?php echo new URL('current_cookies.html'); ?>">current cookies page</a>.
    You can read more about how this site uses your information on the <a href="<?php echo new URL('current_cookies.html'); ?>">privacy page</a>.
</p>

<?php

$form = Cookies::form();
if ($form->handle()) {
    throw new RefreshException();
}
echo $form;
