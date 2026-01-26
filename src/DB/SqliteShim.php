<?php

namespace DigraphCMS\DB;

use PDO;
use Pdo\Sqlite;
use RuntimeException;

class SqliteShim
{

    public static function createFunctions(PDO $pdo): void
    {
        static::createFunction($pdo, 'JSON_VALUE', 2);
        static::createFunction($pdo, 'CONCAT');
        static::createFunction($pdo, 'RAND', 0);
    }

    /**
     * @param PDO $pdo
     * @param string $name
     * @param integer $args
     * @return void
     */
    protected static function createFunction(PDO $pdo, string $name, int $args = -1): void
    {
        if (method_exists($pdo, 'createFunction'))
            $pdo->createFunction($name, self::$name(...), $args);
        elseif (method_exists($pdo, 'sqliteCreateFunction'))
            $pdo->sqliteCreateFunction($name, self::$name(...), $args);
        else
            throw new RuntimeException('PDO does not support user-defined functions for SQLite.');
    }

    /**
     * Generates a random float between 0 and 1.
     */
    public static function RAND(): float
    {
        return mt_rand() / mt_getrandmax();
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
            }
            else {
                return null;
            }
        }
        if ($out === true)
            return 'true';
        if ($out === false)
            return 'false';
        $out = @"$out";
        return $out;
    }

}
