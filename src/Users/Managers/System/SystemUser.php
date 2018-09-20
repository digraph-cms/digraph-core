<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\System;

use Digraph\Users\Managers\Null\NullUser;

class SystemUser extends NullUser
{
    public function name() : string
    {
        return 'System user '.$this->identifier;
    }
}
