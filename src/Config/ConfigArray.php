<?php

namespace DigraphCMS\Config;

use Flatrr\Config\Config;

class ConfigArray extends Config
{
    protected $_arrayData = [];

    public function __construct()
    {
        $this->set(null, json_decode(file_get_contents(__DIR__ . '/default.json'), true));
    }
}
