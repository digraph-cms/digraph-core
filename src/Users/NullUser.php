<?php

namespace DigraphCMS\Users;

use DigraphCMS\URL\URL;

class NullUser extends User
{
    public function __toString()
    {
        if ($this->uuid() == 'guest') {
            return "<a href='" . $this->profile() . "' class='user-link null-user-link'><em>" . $this->name() . "</em></a>";
        } else {
            return "<a class='user-link null-user-link'><em>" . $this->name() . "</em></a>";
        }
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
