<?php

namespace DigraphCMS\DataObjects;

use DateTime;
use Destructr\DSO;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class DSOObject extends DSO
{
    public function uuid(): ?string
    {
        return $this['dso.id'];
    }

    public function created(): DateTime
    {
        return (new DateTime)->setTimezone($this['dso.created.date']);
    }

    public function updated(): DateTime
    {
        return (new DateTime)->setTimezone($this['dso.updated.date']);
    }

    public function createdBy(): User
    {
        return Users::user($this['dso.created.user']);
    }

    public function updatedBy(): User
    {
        return Users::user($this['dso.updated.user']);
    }

    public function createdByUUID(): string
    {
        return $this['dso.created.user'];
    }

    public function updatedByUUID(): string
    {
        return $this['dso.updated.user'];
    }
}
