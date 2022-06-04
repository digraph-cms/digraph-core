<?php

namespace DigraphCMS\DataObjects;

/**
 * Extend this class to create a statically-accessible factory,
 * so that it can be used conveniently without needing to explicitly
 * instantiate it through DataObjects::factory()
 */
abstract class AbstractStaticDSOFactory
{
    protected static $factory;

    abstract protected static function name(): string;

    public static function query(): DSOQuery
    {
        return static::factory()->query();
    }

    public static function get(string $uuid): ?DSOObject
    {
        return static::factory()->get($uuid);
    }

    protected static function factory(): DSOFactory
    {
        if (!static::$factory) static::$factory = DataObjects::factory(static::name());
        return static::$factory;
    }
}
