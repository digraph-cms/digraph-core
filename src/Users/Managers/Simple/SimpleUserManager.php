<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Simple;

use Digraph\Users\Managers\AbstractUserManager;

class SimpleUserManager extends AbstractUserManager
{
    public function create(string $username, string $email, string $password) : bool
    {
        return false;
    }
}
