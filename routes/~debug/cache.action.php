<h1>Testing caching</h1>
<?php

use DigraphCMS\Cache\Cache;
use DigraphCMS\URL\URL;

$value = Cache::get(
    'debug',
    function () {
        var_dump('doing a slow thing!');
        sleep(1);
        return [new URL('?rand=' . random_int(1, 1000))];
    },
    10
);

var_dump($value);
