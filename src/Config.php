<?php

namespace DigraphCMS;

use DigraphCMS\Initialization\InitializationState;
use DigraphCMS\Initialization\InitializedClassInterface;
use DigraphCMS\Initialization\Initializer;
use Flatrr\SelfReferencingFlatArray;
use Spyc;

class Config implements InitializedClassInterface
{
    /** @var SelfReferencingFlatArray */
    protected static $data;

    public static function initialize_preCache(InitializationState $state)
    {
        $state->merge(static::parseJsonFile(__DIR__ . '/config.json'));
        $state->merge(static::parseYamlFile(__DIR__ . '/config.yaml'));
    }

    public static function initialize_postCache(InitializationState $state)
    {
        static::$data = clone $state;
    }

    public static function data(SelfReferencingFlatArray $set = null): SelfReferencingFlatArray
    {
        if ($set) {
            self::$data = $set;
        }
        if (!self::$data) {
            self::$data = new SelfReferencingFlatArray();
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

    public static function parseYamlFile(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        return Spyc::YAMLLoadString(file_get_contents($file));
    }

    public static function parseJsonFile(string $file)
    {
        if (!is_file($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true);
    }
}

Initializer::runClass(Config::class);
