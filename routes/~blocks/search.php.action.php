<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\UI\Forms\PageField;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;

Context::response()->template('iframe.php');
Theme::addBlockingPageJs('/~blocks/editor-integration.js');

$form = new Form('');
$form['page'] = new PageField('Page');
$form['page']->required(true);

if ($form->handle()) {
    $url = new URL('search_results.php');
    $url->query([
        'page' => $form['page']->value(),
        'editor' => Context::arg('editor')
    ]);
    echo "<iframe src='$url' class='embedded-iframe'></iframe>";
}

echo $form;