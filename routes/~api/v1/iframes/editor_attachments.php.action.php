<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Theme;

Context::response()->template('iframe.php');
Theme::addBlockingPageJs('/page/editor-attachments/script.js');
Theme::addInternalPageCss('/page/editor-attachments/styles.scss');

$page = Pages::get(Context::arg('page'));
if (!$page) {
    throw new HttpError(404);
}

echo "TODO: enable blocks editing and creating interface, start with file/image uploading and embedding";
