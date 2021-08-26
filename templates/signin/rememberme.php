<?php

use DigraphCMS\Context;

?>
<h2>Stay signed in?</h2>
<p>Would you like to stay signed in with this browser, and reduce the number of sign in requests for this computer? You should not choose to stay signed in on a shared computer.</p>
<ul class='sign-in-rememberme-buttons'>
    <li>
        <a class="button" href="<?php echo Context::fields()['yes_url']; ?>">Trust this computer and keep me signed in</a>
        <small>You will stay logged in on this computer</small>
    </li>
    <li>
        <a class="button" href="<?php echo Context::fields()['no_url']; ?>">Sign out when I close the browser</a>
        <small>You will be signed out when you close your browser</small>
    </li>
</ul>