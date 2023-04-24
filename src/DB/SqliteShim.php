<?php

namespace DigraphCMS\DB;

use PDO;

class SqliteShim
{
    public static function createFunctions(PDO $pdo): void
    {
        static::createFunction($pdo, 'JSON_VALUE', 2);
        static::createFunction($pdo, 'CONCAT');
    }

    protected static function createFunction(PDO $pdo, string $name, int $args = -1): void
    {
        $pdo->sqliteCreateFunction($name, self::class . '::' . $name, $args);
    }

    public static function CONCAT(): string
    {
        return implode('', func_get_args());
    }

    public static function JSON_VALUE(string $json, string $path): mixed
    {
        $path = substr($path, 2);
        $path = explode('.', $path);
        $arr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $out = &$arr;
        while ($key = array_shift($path)) {
            if (isset($out[$key])) {
                $out = &$out[$key];
            } else {
                return null;
            }
        }
        if ($out === true) return 'true';
        if ($out === false) return 'false';
        $out = @"$out";
        return $out;
    }
}