<?php

namespace DigraphCMS\Content\Blocks;

use ArrayAccess;
use DateTime;
use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Digraph;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Flatrr\FlatArrayTrait;

abstract class AbstractBlock implements ArrayAccess
{
    use FlatArrayTrait {
        set as protected rawSet;
        unset as protected rawUnset;
    }

    protected $uuid, $pageUUID;
    protected $created, $created_by;
    protected $updated, $updated_by;

    abstract public function class(): string;

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid();
        $this->pageUUID = @$metadata['page_uuid'];
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'];
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = @$metadata['updated_by'];
        $this->rawSet(null, $data);
        $this->changed = false;
    }

    public function page(): ?Page
    {
        return Pages::get($this->pageUUID);
    }

    public function pageUUID(): ?string
    {
        return $this->pageUUID;
    }

    public function insert()
    {
        return Blocks::insert($this);
    }

    public function update()
    {
        return Blocks::update($this);
    }

    public function delete()
    {
        return Blocks::delete($this);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function createdBy(): User
    {
        return $this->created_by ? Users::user($this->created_by) : Users::guest();
    }

    public function updatedBy(): User
    {
        return $this->updated_by ? Users::user($this->updated_by) : Users::guest();
    }

    public function createdByUUID(): ?string
    {
        return $this->created_by;
    }

    public function updatedByUUID(): ?string
    {
        return $this->updated_by;
    }

    public function created(): DateTime
    {
        return clone $this->created;
    }

    public function updated(): DateTime
    {
        return clone $this->updated;
    }

    public function updatedLast(): DateTime
    {
        return clone $this->updated_last;
    }
}