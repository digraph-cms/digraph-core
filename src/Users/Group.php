<?php

namespace DigraphCMS\Users;

use DigraphCMS\URL\URL;

class Group
{
    protected $uuid, $name;

    public function __construct(string $uuid, string $name)
    {
        $this->uuid = $uuid;
        $this->name = $name;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function url(): URL
    {
        if ($this->uuid == 'users') {
            return new URL('/~users/');
        }
        return new URL('/~groups/' . $this->uuid() . '.html');
    }

    public function __toString()
    {
        if ($this->uuid == 'guests') {
            return "<a class='user-group-link user-group-null-link'><em>" . $this->name() . "</em></a>";
        }else {
            return "<a href='" . $this->url() . "' class='user-group-link'>" . $this->name() . "</a>";
        }
    }
}
