<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Null;

use Digraph\Users\Managers\AbstractUserManager;

class NullUserManager extends AbstractUserManager
{
    public function create(string $username, string $email, string $password) : bool
    {
        return false;
    }
}
