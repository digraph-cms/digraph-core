<?php

namespace DigraphCMS\DB;

use PDO;

class SqliteShim
{
    public static function createFunctions(PDO $pdo)
    {
        static::createFunction($pdo, 'JSON_VALUE', 2);
        static::createFunction($pdo, 'CONCAT');
    }

    protected static function createFunction(PDO $pdo, string $name, int $args = -1)
    {
        $pdo->sqliteCreateFunction($name, self::class . '::' . $name, $args);
    }

    public static function CONCAT()
    {
        return implode('', func_get_args());
    }

    public static function JSON_VALUE(string $json, string $path)
    {
        $path = substr($path, 2);
        $path = explode('.', $path);
        $arr = json_decode($json, true);
        $out = &$arr;
        while ($key = array_shift($path)) {
            if (isset($out[$key])) {
                $out = &$out[$key];
            } else {
                return null;
            }
        }
        $out = @"$out";
        return $out;
    }
}
