<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */

namespace Digraph\Users\Managers\Simple;

use Digraph\Users\Managers\AbstractUserManager;
use Digraph\Users\UserInterface;

class SimpleUserManager extends AbstractUserManager
{
    const DSO_TYPE = 'simple';

    public function create(): ?UserInterface
    {
        $user = $this->cms->factory('users')->create([
            'dso.type' => static::DSO_TYPE
        ]);
        $user->managerName($this->name());
        return $user;
    }

    public function getByIdentifier(string $identifier): ?UserInterface
    {
        $out = $this->cms->factory('users')->read($identifier);
        if ($out) {
            $out->managerName($this->name());
        }
        return $out;
    }

    public function getByEmail(string $email): ?UserInterface
    {
        $email = strtolower($email);
        $search = $this->cms->factory('users')->search();
        $search->where('${email.primary} = :email AND ${dso.type} = :type');
        $res = $search->execute([
            'email' => $email,
            'type' => static::DSO_TYPE
        ]);
        if ($res) {
            $out = array_pop($res);
            $out->managerName($this->name());
            return $out;
        }
        return null;
    }
}
