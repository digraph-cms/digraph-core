<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\UI\DataTables\PageTable;

echo "<h1>Pages</h1>";
echo new PageTable(Pages::select()->order('updated desc'));
