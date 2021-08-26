<?php

use DigraphCMS\Config;
use DigraphCMS\Session\Session;

include "../vendor/autoload.php";

Config::readFile(__DIR__ . '/../env.json');
Config::merge([
    'paths.base' => __DIR__,
    'paths.web' => __DIR__
]);

var_dump(Session::authentication());

// Session::authenticate('c04cd19d-8aa8-d529-2013-d0f17c6ffc6a', 'test signin', true);
