<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Null;

use Digraph\Users\Managers\AbstractUserManager;
use Digraph\Users\UserInterface;

class NullUserManager extends AbstractUserManager
{
    const USERCLASS = NullUser::class;

    public function getByIdentifier(string $identifier) : ?UserInterface
    {
        if (!$identifier) {
            return null;
        }
        $class = static::USERCLASS;
        return new $class($identifier, $this->name);
    }

    public function getByEmail(string $email) : ?UserInterface
    {
        //Null users don't have emails
        return null;
    }
}
