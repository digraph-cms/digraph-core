<?php

namespace DigraphCMS\Users;

use DigraphCMS\URL\URL;

class Group
{
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function url(): URL
    {
        if ($this->name == 'users') {
            return new URL('/~users/');
        }
        return new URL('/~groups/' . $this->name() . '.html');
    }

    public function __toString()
    {
        return "<a href='" . $this->url() . "' class='user-group-link'>" . $this->name() . "</a>";
    }
}
