<?php

namespace DigraphCMS\Users;

use DigraphCMS\HTML\A;
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
        $a = (new A)
            ->addClass('group-link')
            ->addChild($this->name());
        if ($this->uuid() == 'guests') {
            $a->addClass('group-link--null');
            $a->addClass('group-link--guests');
        } else {
            $url = $this->url();
            if (Permissions::url($url)) {
                $a
                    ->setAttribute('href', $url)
                    ->setAttribute('target', '_top');
            }
        }
        return $a->__toString();
    }
}
