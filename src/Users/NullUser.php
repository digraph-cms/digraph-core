<?php

namespace DigraphCMS\Users;

class NullUser extends User
{
    public function html(): string
    {
        return "<a class='user-link null-user-link'>" . $this->name() . "<a>";
    }

    public function insert()
    {
        // does nothing, null users shouldn't be put into the database
    }

    public function update()
    {
        // does nothing, null users shouldn't be put into the database
    }
}
