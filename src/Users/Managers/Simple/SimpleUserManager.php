<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Simple;

use Digraph\Users\Managers\AbstractUserManager;
use Digraph\Users\UserInterface;

class SimpleUserManager extends AbstractUserManager
{
    const USERCLASS = SimpleUser::class;

    public function getByIdentifier(string $identifier) : ?UserInterface
    {
        //TODO: search
        return null;
    }

    public function getByEmail(string $email) : ?UserInterface
    {
        //TODO: search
        return null;
    }
}
