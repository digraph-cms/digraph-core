<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Null;

use Digraph\Users\Managers\AbstractUserManager;
use Digraph\Users\UserInterface;

class NullUserManager extends AbstractUserManager
{
    const USERCLASS = NullUser::class;
    const MANAGER = 'null';

    public function getByIdentifier(string $identifier) : ?UserInterface
    {
        $class = static::USERCLASS;
        return new $class($identifier, static::MANAGER);
    }

    public function getByEmail(string $email) : ?UserInterface
    {
        //Null users don't have emails
        return null;
    }
}
