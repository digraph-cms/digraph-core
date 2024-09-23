<?php

namespace DigraphCMS\Content;

use DateTime;
use DigraphCMS\HTML\DIV;
use DigraphCMS\Media\File;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Templates;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Mimey\MimeTypes;

class FilestoreFile extends File
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
            return file_get_contents($this->path());
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
        else return
            Permissions::inMetaGroup('content__editor', $user)
            || call_user_func(
                $this->permissions(),
                Users::current() ?? Users::guest(),
                $this
            );
    }

    public function embed(): string
    {
        return Templates::render('content/embed-file.php', ['file' => $this]);
    }

    /**
     * Override to return filestore/file:uuid URL
     *
     * @return string
     */
    public function url(): string
    {
        return Filestore::url($this->uuid());
    }

    /**
     * Override to do nothing on write() because files are written to the
     * filestore storage directory only.
     *
     * @return void
     */
    public function write()
    {
        // does nothing, because files live in the filestore storage directory only
    }

    /**
     * Override path to return direct Filestore path because files are stored
     * in the filestore storage directory only.
     *
     * @return string
     */
    public function path(): string
    {
        return Filestore::path($this->hash());
    }

    public function delete(): bool
    {
        return Filestore::delete($this);
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
