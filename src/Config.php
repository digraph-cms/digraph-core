<?php

namespace DigraphCMS;

use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\InitializedClassInterface;
use DigraphCMS\Cache\CachedInitializer;
use Flatrr\SelfReferencingFlatArray;
use Spyc;

abstract class Config implements InitializedClassInterface
{
    /** @var SelfReferencingFlatArray|null */
    protected static $data;

    /**
     * A globally-available prefix to be used by things like caches and static
     * files for keeping them separate based on the environment being called.
     * This will allow different caches when a site is accessed via different
     * URLs or roots, even if they use the same base cache directory.
     * 
     * @return string 
     */
    public static function envPrefix(): string
    {
        static $cache;
        return $cache ??
            $cache = crc32(serialize([
                $_SERVER['DOCUMENT_ROOT'],
                $_SERVER['SERVER_PORT'],
                $_SERVER['SERVER_NAME']
            ]));
    }

    public static function initialize_preCache(CacheableState $state): void
    {
        $state->merge(static::parseJsonFile(__DIR__ . '/config.json'), null, true);
        $state->merge(static::parseYamlFile(__DIR__ . '/config.yaml'), null, true);
    }

    public static function initialize_postCache(CacheableState $state): void
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

    public static function secret(): string
    {
        return static::get('secret') ?? static::generatedSecret();
    }

    protected static function generatedSecret(): string
    {
        static $cache;
        if (!$cache) {
            $file = static::get('paths.storage') . '/secret.txt';
            if (!file_exists($file)) file_put_contents($file, bin2hex(random_bytes(32)));
            $cache = file_get_contents($file);
        }
        return $cache;
    }

    public static function get(string $key = null): mixed
    {
        return self::$data->get($key);
    }

    public static function set(?string $key, mixed $value): mixed
    {
        return self::$data->set($key, $value);
    }

    public static function merge(mixed $value, string $name = null, bool $overwrite = true): void
    {
        self::$data->merge($value, $name, $overwrite);
    }

    /**
     * @param string $file
     * @return array<mixed,mixed>
     */
    public static function parseYamlFile(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        return Spyc::YAMLLoadString(file_get_contents($file));
    }

    /**
     * @param string $file
     * @return array<mixed,mixed>
     */
    public static function parseJsonFile(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<mixed,mixed> $data
     * @param bool $minify
     * @return string
     */
    public static function dumpJson(array $data, bool $minify = false): string
    {
        return json_encode($data, $minify ? JSON_PRETTY_PRINT : null);
    }

    /**
     * @param array<mixed,mixed> $data
     * @return string
     */
    public static function dumpYaml(array $data): string
    {
        return Spyc::YAMLDump(
            $data,
            2,
            80,
            true
        );
    }
}

CachedInitializer::runClass(Config::class);
