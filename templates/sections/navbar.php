<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\UI\MenuBar\MenuBar;

$menu = (new MenuBar)
    ->setID('main-nav');
if ($home = Pages::get('home')) {
    $menu->addPage($home, 'Home');
    foreach ($home->children() as $child) {
        $menu->addPage($child);
    }
    echo $menu;
}
