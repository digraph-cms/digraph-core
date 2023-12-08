<?php

namespace DigraphCMS\Content;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\FS;
use DigraphCMS\HTML\DIV;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\ImageFile;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Mimey\MimeTypes;

class FilestoreFile extends DeferredFile
{
    protected $uuid, $hash, $filename, $parent, $meta, $image;
    protected $bytes, $created, $created_by;

    public function __construct(
        string $uuid,
        string $hash,
        string $filename,
        int $bytes,
        string $parent,
        array $meta,
        int $created,
        ?string $created_by,
        null|callable $permissions = null
    ) {
        $this->uuid = $uuid;
        $this->hash = $this->identifier = $hash;
        $this->filename = $filename;
        $this->bytes = $bytes;
        $this->parent = $parent;
        $this->meta = $meta;
        $this->created = (new DateTime())->setTimestamp($created);
        $this->created_by = $created_by;
        $this->content = function () {
            FS::mkdir(dirname($this->path()));
            FS::copy(Filestore::path($this->hash), $this->path(), Config::get('filestore.symlink'));
        };
        $this->permissions = $permissions;
    }

    public function card(?string $name = null, bool $nofollow = false, array $display_meta = ['upload_date']): DIV
    {
        $card = parent::card($name, $nofollow);
        // add requested metadata
        $meta = [];
        if (in_array('size', $display_meta)) {
            $meta[] = Format::filesize($this->bytes());
        }
        if (in_array('uploader', $display_meta) && in_array('upload_date', $this['meta'])) {
            $meta[] = 'created ' . Format::date($this->created()) . ' by ' . $this->createdBy();
        } else {
            if (in_array('upload_date', $display_meta)) {
                $meta[] = 'created ' . Format::date($this->created());
            }
            if (in_array('uploader', $display_meta)) {
                $meta[] = 'created by ' . $this->createdBy();
            }
        }
        if (in_array('hash', $display_meta)) {
            $meta[] = 'MD5 ' . $this->hash();
        }
        $card->addChild((new DIV)
                ->addClass('file-card__meta')
                ->addChild(implode('<br>', $meta))
        );
        return $card;
    }

    public function checkPermissions(User|null $user = null): bool
    {
        if (is_null($this->permissions())) return true;
        else return call_user_func(
                $this->permissions(),
                $user ?? Users::current() ?? Users::guest(),
                $this
            );
    }

    public function embed(): string
    {
        return Templates::render('content/embed-file.php', ['file' => $this]);
    }

    /**
     * Override to return files/filestore:uuid URL if permissions are set
     *
     * @return string
     */
    public function url(): string
    {
        if ($this->permissions()) return (new URL('/permissioned_files/filestore:' . $this->uuid()))->__toString();
        else return parent::url();
    }

    /**
     * Override to do nothing on write() calls if permissions are set, since path
     * is the direct Filestore path.
     *
     * @return void
     */
    public function write()
    {
        if ($this->permissions) {
            // do nothing if file is permissioned, because we'll read it directly from the filestore
        } else {
            parent::write();
        }
    }

    /**
     * Override path to return direct Filestore path if permissions are set, so
     * we can read directly from there.
     *
     * @return string
     */
    public function path(): string
    {
        if ($this->permissions()) {
            return Filestore::path($this->hash());
        } else {
            return parent::path();
        }
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

    /**
     * Get the full parent string of this file.
     * 
     * @return string 
     */
    public function parent(): string
    {
        return $this->parent;
    }

    /**
     * Return just the UUID portion of the parent string (strip off /whatever from the end)
     * 
     * @return string 
     */
    public function parentUUID(): string
    {
        return preg_replace('/\/.*$/', '', $this->parent());
    }

    public function mediaUUID(): string
    {
        return $this->parent;
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