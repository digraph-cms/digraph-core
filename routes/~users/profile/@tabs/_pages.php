<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\UI\Pagination\PageTable;

$pages = Pages::select()
    ->where('(page.created_by = ? OR page.updated_by = ?)', [$user->uuid(), $user->uuid()])
    ->order('updated DESC');

if ($pages->count()) {
    echo "<h1>Pages created/modified</h1>";
    echo "<p>The following pages were created or last modified by this user.</p>";
    echo new PageTable($pages);
}
