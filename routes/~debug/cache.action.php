<h1>Testing caching</h1>
<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\Content\Pages;

$value = Cache::get(
    'debug',
    function () {
        var_dump('doing a slow thing!');
        return Pages::get('3453e110');
    }
);

var_dump($value);
