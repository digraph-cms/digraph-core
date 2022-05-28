<?php

namespace DigraphCMS\DB;

use PDO;

class SqliteShim
{
    public static function createFunctions(PDO $pdo)
    {
        $pdo->sqliteCreateFunction('JSON_VALUE', self::class . '::JSON_VALUE', 2);
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
