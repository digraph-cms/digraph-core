<?php

namespace DigraphCMS\Media;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\URL\URL;

class PermissionedFile extends File
{
    public function write()
    {
        parent::write();
        Cache::set(
            'media/permissioned_file/filename_' . $this->identifier(),
            $this->filename(),
            $this->ttl()
        );
        Cache::set(
            'media/permissioned_file/permissions_' . $this->identifier(),
            $this->permissions(),
            $this->ttl()
        );
    }

    protected $permissions = null;

    public function setPermissions(callable $callback): static
    {
        $this->permissions = $callback;
        return $this;
    }

    public function permissions(): callable
    {
        return $this->permissions
            ?? fn() => false;
    }

    public function url(): string
    {
        $this->write();
        return (new URL('/assets/file:' . $this->identifier()))->__toString();
    }

    public function path(): string
    {
        return Config::get('cache.path') . '/media/permissioned_files/' . $this->identifier() . '/' . $this->filename();
    }

    public static function buildPath(string $identifier, string $filename): string
    {
        return Config::get('cache.path') . '/media/permissioned_files/' . $identifier . '/' . $filename;
    }
}