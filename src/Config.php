<?php

namespace DigraphCMS;

use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\InitializedClassInterface;
use DigraphCMS\Cache\CachedInitializer;
use Flatrr\SelfReferencingFlatArray;
use Spyc;

class Config implements InitializedClassInterface
{
    /** @var SelfReferencingFlatArray */
    protected static $data;

    public static function initialize_preCache(CacheableState $state)
    {
        $state->merge(static::parseJsonFile(__DIR__ . '/config.json'), null, true);
        $state->merge(static::parseYamlFile(__DIR__ . '/config.yaml'), null, true);
    }

    public static function initialize_postCache(CacheableState $state)
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

    public static function dumpJson(array $data, $minify = false): string
    {
        return json_encode($data, $minify ? JSON_PRETTY_PRINT : null);
    }

    public static function dumpYaml(array $data): string
    {
        return Spyc::YAMLDump(
            $data,
            false,
            80,
            true
        );
    }
}

CachedInitializer::runClass(Config::class);
