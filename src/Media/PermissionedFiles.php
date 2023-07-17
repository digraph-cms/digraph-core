<?php

namespace DigraphCMS\Media;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\URL\URL;

class PermissionedFiles
{
    public static function prepare(
        string $identifier,
        string $filename,
        callable $permissions,
        int $ttl,
        string $path = null,
    ) {
        Cache::set(
            'media/permissioned_files/filename/' . $identifier,
            $filename,
            $ttl
        );
        Cache::set(
            'media/permissioned_files/path/' . $identifier,
            $path ?? static::path($identifier),
            $ttl
        );
        Cache::set(
            'media/permissioned_files/permissions/' . $identifier,
            $permissions,
            $ttl
        );
    }

    public static function url(string $identifier, string $filename): URL
    {
        return (new URL('/assets/file:' . $identifier))
            ->setName($filename);
    }

    public static function path(string $identifier): string
    {
        return Config::get('cache.path') . '/media/permissioned_files/file/' . $identifier;
    }
}