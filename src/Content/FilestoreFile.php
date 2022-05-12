<?php

namespace DigraphCMS\Content;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\FS;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\ImageFile;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\Templates;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Mimey\MimeTypes;

class FilestoreFile extends DeferredFile
{
    protected $uuid, $hash, $filename, $media, $meta, $image;

    public function __construct(string $uuid, string $hash, string $filename, int $bytes, string $parent, array $meta, int $created, ?string $created_by)
    {
        $this->uuid = $uuid;
        $this->hash = $this->identifier = $hash;
        $this->filename = $filename;
        $this->bytes = $bytes;
        $this->media = $parent;
        $this->meta = $meta;
        $this->created = (new DateTime())->setTimestamp($created);
        $this->created_by = $created_by;
        $this->content = function () {
            FS::mkdir(dirname($this->path()));
            FS::copy(Filestore::path($this->hash), $this->path(), Config::get('filestore.symlink'));
        };
    }

    public function embed(): string
    {
        return Templates::render('content/embed-file.php', ['file' => $this]);
    }

    public function delete(): bool
    {
        return Filestore::delete($this);
    }

    public function src(): string
    {
        return Filestore::path($this->hash);
    }

    public function image(): ?ImageFile
    {
        if (!ImageFile::handles($this->extension())) {
            return null;
        } else {
            return new ImageFile($this->src(), $this->filename);
        }
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function bytes(): int
    {
        return $this->bytes;
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
    }

    public function mime(): string
    {
        return (new MimeTypes())->getMimeType($this->extension());
    }

    public function media(): AbstractRichMedia
    {
        return RichMedia::get($this->media);
    }

    public function mediaUUID(): string
    {
        return $this->media;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function createdBy(): User
    {
        return $this->created_by ? Users::user($this->created_by) : Users::guest();
    }

    public function createdByUUID(): ?string
    {
        return $this->created_by;
    }

    public function created(): DateTime
    {
        return clone $this->created;
    }
}
