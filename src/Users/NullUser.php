<?php

namespace DigraphCMS\Users;

class NullUser extends User
{
    public function __toString()
    {
        $name = $this->name();
        if ($this->uuid() != 'guest') {
            $name = "<s>$name</s>";
        } else {
            $name = "<em>$name</em>";
        }
        return "<a class='user-link null-user-link'>$name<a>";
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
            return [new Group('guests')];
        } else {
            return [];
        }
    }
}
