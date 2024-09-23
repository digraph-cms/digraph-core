<?php

namespace DigraphCMS\Media;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\FS;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;

class File
{
    protected $filename, $extension, $content, $identifier, $written, $src, $url;
    /** @var callable|null */
    protected $permissions;

    public function __construct(string $filename, string|callable $content, $identifier = null, callable|null $permissions = null)
    {
        // take in filename/extension
        $this->filename = $filename;
        $this->extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        // double check extension is valid
        if (strlen($this->extension) == 0 || preg_match('/[^a-z0-9]/', $this->extension)) {
            throw new \Exception("Filename $filename has an invalid extension");
        }
        // take in content/identifier
        $this->content = $content;
        $this->identifier = $identifier ? md5(serialize($identifier)) : md5($content);
        // permissions
        $this->permissions = $permissions;
    }

    public function card(string $name = null, bool $nofollow = false): DIV
    {
        $card = (new DIV())
            ->addClass('file-card')
            ->addClass('card')
            ->addClass('file-card--extension-' . $this->extension());
        // add card title
        $card->addChild((new DIV)
            ->addClass('card__title')
            ->addChild($a = (new A())
                ->addChild($name ?? $this->filename())
                ->setAttribute('title', $this->filename())
                ->setAttribute('href', $this->url())));
        if ($nofollow) {
            $a->setAttribute('rel', 'nofollow');
        }
        return $card;
    }

    public function permissions(): null|callable
    {
        return $this->permissions;
    }

    public function image(): ?ImageFile
    {
        if ($this instanceof ImageFile) return $this;
        if (!ImageFile::handles($this->extension())) return null;
        return new ImageFile($this->path(), $this->filename(), $this->permissions());
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function extension(): string
    {
        return $this->extension;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function path(): string
    {
        return Media::filePath($this, !is_null($this->permissions()));
    }

    public function url(): string
    {
        $this->write();
        if (!$this->url) {
            $this->url = Media::fileUrl($this, !is_null($this->permissions()));
        }
        return $this->url;
    }

    public function ttl(): int
    {
        return Config::get('files.ttl') ?? 0;
    }

    public function write()
    {
        if ($this->written) {
            return;
        }
        $this->written = true;
        // if output file already exists and files.ttl config exists, don't
        // write file again if its age is less than files.ttl
        if (is_file($this->path()) && $this->ttl()) {
            if (time() < (filemtime($this->path()) + $this->ttl())) {
                return;
            }
        }
        // create directory and put content in file
        FS::dump($this->path(), $this->content());
        // cache file location and permissions
        if ($this->permissions()) {
            Cache::set(
                'permissioned_media/info/' . $this->identifier(),
                [
                    'path' => $this->path(),
                    'filename' => $this->filename(),
                    'permissions' => $this->permissions(),
                ],
                // cache for twice TTL just to ensure permissions stay accessible in some edge cases
                $this->ttl() * 2
            );
        }
    }

    public function content(): string
    {
        if (is_callable($this->content)) {
            $this->content = call_user_func($this->content);
        }
        return $this->content;
    }
}
