<?php

namespace DigraphCMS\Users;

class NullUser extends User
{
    public function __toString()
    {
        $name = $this->name();
        if ($this->uuid() != 'guest') {
            $name = "<s>$name</s>";
        }else {
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

    public function groups(): array
    {
        return ['null users'];
    }
}
