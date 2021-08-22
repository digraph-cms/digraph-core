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
    protected $groups;

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

    /**
     * Get all the groups to which this user belongs
     *
     * @return Group[]
     */
    public function groups(): array
    {
        if ($this->groups === null) {
            $this->groups = ['users'];
            $this->groups = array_merge($this->groups, Users::groups($this->uuid()));
            $this->groups = array_map(
                function ($group) {
                    return new Group($group);
                },
                $this->groups
            );
        }
        return $this->groups;
    }

    public function __toString()
    {
        return "<a href='" . $this->profile() . "' class='user-link'>" . $this->name() . "<a>";
    }

    public function addEmail(string $email, string $comment = '')
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email address");
        }
        foreach ($this['emails'] ?? [] as $k => $existing) {
            if ($existing[0] == $email) {
                $this['emails'][$k] = [$email, time(), $comment];
                return;
            }
        }
        $this->push('emails', [$email, time(), $comment]);
    }

    public function removeEmail(string $email)
    {
        $emails = array_filter(
            $this['emails'],
            function ($e) use ($email) {
                return $email != $e[0];
            }
        );
        unset($this['emails']);
        $this['emails'] = $emails;
    }

    public function emails()
    {
        return array_map(
            function ($e) {
                return $e[0];
            },
            $this['emails'] ?? []
        );
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
        return new URL('/~users/' . $this->uuid() . '.html');
    }

    public function insert()
    {
        return Users::insert($this);
    }

    public function update()
    {
        return Users::update($this);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function createdBy(): User
    {
        return Users::user($this->created_by);
    }

    public function updatedBy(): User
    {
        return Users::user($this->updated_by);
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
