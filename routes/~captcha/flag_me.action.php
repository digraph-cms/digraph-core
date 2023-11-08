<h1>Test flagging</h1>
<?php

use DigraphCMS\Security\Security;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;

echo '<h2>My IP</h2>';
if (Security::ipFlagged()) Notifications::printWarning('Currently flagged');
else echo (new CallbackLink(Security::flagIP(...)))->addChild('Flag my IP');

if (!Session::user()) return;

echo '<h2>My account</h2>';
if (Security::userFlagged()) Notifications::printWarning('Currently flagged');
else echo (new CallbackLink(Security::flagUser(...)))->addChild('Flag my account');

echo '<h2>My authentication</h2>';
if (Security::authenticationFlagged()) Notifications::printWarning('Currently flagged');
else echo (new CallbackLink(Security::flagAuthentication(...)))->addChild('Flag my authentication');