<?php

use DigraphCMS\Content\Graph;
use DigraphCMS\Content\Pages;
use DigraphCMS\UI\MenuBar\MenuBar;

$menu = (new MenuBar)
    ->setID('main-nav');
if ($home = Pages::get('home')) {
    $menu->addPage($home, 'Home');
    $children = Graph::children($home->uuid(), 'normal')
        ->order('name asc');
    foreach ($children as $child) {
        $menu->addPage($child);
    }
    echo $menu;
}
