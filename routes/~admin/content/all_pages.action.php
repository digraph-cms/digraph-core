<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\UI\Pagination\PageTable;

Context::response()->enableCache();

echo "<h1>Pages</h1>";
echo new PageTable(Pages::select()->order('updated desc'));
