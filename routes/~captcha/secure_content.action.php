<h1>Testing secure content class</h1>
<?php

use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\Security\SecureContent;

echo '<h2>Arbitrary secure content</h2>';
$content = new SecureContent();
$content->addChild('This content is not visible to suspicious characters or bots.');
echo $content;

echo '<h2>Secure content form</h2>';
$form = new FormWrapper();
echo $form;