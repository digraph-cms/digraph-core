<h1>CAPTCHA test</h1>
<?php

use DigraphCMS\Digraph;
use DigraphCMS\Security\SecureContent;
use DigraphCMS\Security\Security;
use DigraphCMS\UI\CallbackLink;

echo (new CallbackLink(fn () => Security::flagAuthentication(null, 'Debug flag')))
    ->addChild('Flag my authentication session')
    ->addClass('button');

echo '<hr>';

echo Digraph::uuid();

echo '<hr>';

$secure = new SecureContent();
$secure->addChild('This content is secured by a CAPTCHA');
echo $secure;