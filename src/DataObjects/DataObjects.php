<?php

namespace DigraphCMS\DataObjects;

use Destructr\DriverFactory;
use Destructr\Drivers\AbstractSQLDriver;
use DigraphCMS\Config;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;

class DataObjects
{
    protected static $factories = [];

    public static function factory(string $name): DSOFactory
    {
        $name = static::normalizeName($name);
        if (!isset(static::$factories[$name])) {
            $class = Config::get("data_objects.$name.class")
                ?? Config::get("data_objects.$name")
                ?? DSOFactory::class;
            static::$factories[$name] = new $class($name);
            static::$factories[$name]->prepareEnvironment();
        }
        return static::$factories[$name];
    }

    public static function updateAllEnvironments(): DeferredJob
    {
        return new DeferredJob(function (DeferredJob $job) {
            foreach (array_keys(Config::get('data_objects')) as $name) {
                $job->spawn(function () use ($name) {
                    $value = static::factory($name)->prepareEnvironment()
                        ? 'true'
                        : 'false';
                    return 'Prepared ' . $name . ' data object table: ' . $value;
                });
                $job->spawn(function () use ($name) {
                    $value = static::factory($name)->updateEnvironment()
                        ? 'true'
                        : 'false';
                    return 'Updated ' . $name . ' data object table: ' . $value;
                });
            }
            return 'Set up data object environment updates';
        });
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
