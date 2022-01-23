<?php

namespace DigraphCMS\Users;

use DigraphCMS\HTML\A;
use DigraphCMS\URL\URL;

class NullUser extends User
{
    public function __toString()
    {
        $a = (new A)
            ->addClass('user-link')
            ->addClass('user-link--null')
            ->addChild($this->name());
        if ($this->uuid() == 'guest') {
            $a->addClass('user-link--guest');
            $url = $this->profile();
            if (Permissions::url($url)) {
                $a->setAttribute('href', $url);
                $a->setAttribute('target', '_top');
            }
        }
        return $a->__toString();
    }

    public function profile(): URL
    {
        return new URL('/~users/_' . $this->uuid() . '.html');
    }

    public function insert()
    {
        // does nothing, null users shouldn't be put into the database
    }

    public function update()
    {
        // does nothing, null users shouldn't be put into the database
    }

    /**
     * Return the groups this user belongs to. Will be empty for all null users
     * except "guest" which will be part of "guests"
     *
     * @return array
     */
    public function groups(): array
    {
        if ($this->uuid() == 'guest') {
            return [new Group('guests', "Guests")];
        } else {
            return [];
        }
    }
}
