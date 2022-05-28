<?php

namespace DigraphCMS\DataObjects;

use Destructr\DriverFactory;
use Destructr\Drivers\AbstractSQLDriver;
use DigraphCMS\DB\DB;

class DataObjects
{
    protected static $factories = [];

    public static function factory(string $name): DSOFactory
    {
        $name = static::normalizeName($name);
        if (!isset(static::$factories[$name])) {
            static::$factories[$name] = new DSOFactory($name);
        }
        return static::$factories[$name];
    }

    protected static function normalizeName(string $name): string
    {
        $name = preg_replace('/[^a-z0-9_]/', '_', $name);
        return $name;
    }

    public static function driver(): AbstractSQLDriver
    {
        static $driver;
        if (!$driver) {
            /** @var AbstractSQLDriver */
            $driver = DriverFactory::factoryFromPDO(DB::pdo());
            $driver->createSchemaTable();
        }
        return $driver;
    }
}
