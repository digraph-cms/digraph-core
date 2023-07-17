<?php

namespace DigraphCMS\Media;

class PermissionedFile extends File
{
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
        return PermissionedFiles::url($this->identifier(), $this->filename());
    }

    public function path(): string
    {
        return PermissionedFiles::path($this->identifier());
    }

    public function write()
    {
        parent::write();
        PermissionedFiles::prepare(
            $this->identifier(),
            $this->filename(),
            $this->permissions(),
            $this->ttl(),
            $this->path(),
        );
    }
}