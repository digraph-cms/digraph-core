<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Route;

$children = Pages::children(Route::page()->uuid());
foreach ($children as $child) {
    var_dump($child);
}