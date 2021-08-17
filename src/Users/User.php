<?php

namespace DigraphCMS\Users;

use ArrayAccess;
use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use Flatrr\FlatArrayTrait;

class User implements ArrayAccess
{
    use FlatArrayTrait {
        set as protected rawSet;
        unset as protected rawUnset;
    }

    protected $uuid, $name;
    protected $created, $created_by;
    protected $updated, $updated_by;
    protected $slugCollisions;

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid();
        $this->name = @$metadata['name'] ?? Users::randomName();
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'] ?? Session::user();
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = @$metadata['updated_by'] ?? Session::user();
        $this->rawSet(null, $data);
        $this->changed = false;
    }

    public function name(string $name = null): string
    {
        if ($name) {
            $this->name = $name;
        }
        return $this->name;
    }

    public function profile(): URL
    {
        return new URL('/~user/profile.html?uuid=' . $this->uuid());
    }

    public function insert()
    {
        return Users::insert($this);
    }

    public function update()
    {
        return Users::update($this);
    }

    public function delete()
    {
        return Users::delete($this);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function createdBy(): string
    {
        return $this->created_by;
    }

    public function updatedBy(): string
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
        return clone $this->updated;
    }
}
