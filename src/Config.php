<?php

namespace DigraphCMS;

use Flatrr\SelfReferencingFlatArray;

class Config
{
    /** @var SelfReferencingFlatArray */
    protected static $data;

    public static function data(SelfReferencingFlatArray $set = null): SelfReferencingFlatArray
    {
        if ($set) {
            self::$data = $set;
        }
        return self::$data;
    }

    public static function get(string $key = null)
    {
        return self::$data->get($key);
    }

    public static function set(?string $key, $value)
    {
        return self::$data->set($key, $value);
    }

    public static function merge($value, $name = null, $overwrite = true)
    {
        self::$data->merge($value, $name, $overwrite);
    }

    public static function readFile($file, $overwrite = true)
    {
        if (!is_file($file)) {
            return;
        }
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $fn = "readFile_$extension";
        self::$data->merge(
            static::$fn($file),
            null,
            $overwrite
        );
    }

    protected static function readFile_json(string $filename): array
    {
        return json_decode(
            file_get_contents($filename),
            true
        );
    }
}
