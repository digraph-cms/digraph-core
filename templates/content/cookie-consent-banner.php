<?php

use DigraphCMS\URL\URL;

$privacy = new URL('/privacy/');
$settings = new URL('/privacy/cookie_authorizations.html');

?>
<p>
    This site uses cookies to improve user experience and analyze website traffic.
    By clicking "Accept" you agree to this cookie use as described on the <a href="<?php echo $settings; ?>" data-target="_top">privacy information page</a>.
    You can change your cookie settings at any time on the <a href="<?php echo $settings; ?>" data-target="_top">cookie authorizations page</a>.
</p>
