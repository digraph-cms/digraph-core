<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

Context::response()->template('framed.php');

echo "<div id='cookie-consent-banner' class='cookie-consent-banner'>";
echo "<div class='cookie-consent-banner__explanation'>";
echo "This site uses cookies to give you the best possible experience by remembering your preferences and managing user interface state across multiple pageviews. ";
echo "By clicking \"Accept all\" you consent to the use of all site cookies. ";
echo "If you would like to know more about what each category of cookies is used for and select which you would like to allow, visit ".(new URL('/~privacy/cookie_authorizations.html'))->html().". ";
echo "</div>";
echo "</div>";