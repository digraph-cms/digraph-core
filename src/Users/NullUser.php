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
        if ($this->uuid() == 'system') {
            $a->addClass('user-link--system');
            $url = $this->profile();
            if (Permissions::url($url)) {
                $a->setAttribute('href', $url);
                $a->setAttribute('target', '_top');
            }
        }
        return $a->__toString();
    }

    public function profile(): ?URL
    {
        if ($this->uuid() == 'system') return parent::profile();
        elseif ($this->uuid() == 'guest') return parent::profile();
        else return null;
    }

    public function insert(): void
    {
        // does nothing, null users shouldn't be put into the database
    }

    public function update(): void
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
        if ($this->uuid() == 'guest') return [new Group('guests', "Guests")];
        if ($this->uuid() == 'system') return [new Group('system', "System"), Users::group('admins')];
        else return [];
    }
}
