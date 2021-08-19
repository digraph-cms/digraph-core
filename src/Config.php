<?php

namespace DigraphCMS;

use Flatrr\Config\Config as FlatrrConfig;

class Config
{
    /** @var FlatrrConfig */
    protected static $config;

    public static function __init()
    {
        self::$config = new FlatrrConfig();
        self::$config->readDir(__DIR__ . '/../config');
        self::merge([
            'paths.system' => realpath(__DIR__ . '/..'),
            'routes.paths.system' => '${paths.system}/routes'
        ]);
    }

    public static function get(string $key = null)
    {
        return self::$config->get($key);
    }

    public static function set(?string $key, $value)
    {
        return self::$config->set($key, $value);
    }

    public static function merge($value, $name = null, $overwrite = true)
    {
        self::$config->merge($value, $name, $overwrite);
    }

    public static function readDir($dir, $name = null, $overwrite = true)
    {
        self::$config->readDir($dir, $name, $overwrite);
    }

    public static function readFile($file, $name = null, $overwrite = true)
    {
        self::$config->readFile($file, $name, $overwrite);
    }
}

Config::__init();