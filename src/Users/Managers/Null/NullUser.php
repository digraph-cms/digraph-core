<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Null;

use Digraph\Users\UserInterface;

class NullUser implements UserInterface
{
    protected $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function identifier() : string
    {
        return $this->identifier;
    }

    public function name() : string
    {
        return 'Null user '.$this->identifier;
    }

    public function email() : ?string
    {
        return null;
    }

    public function setPassword(string $password)
    {
        //does nothing, null users don't have passwords
    }

    public function checkPassword(string $password) : bool
    {
        //does nothing, null users don't have passwords
        return false;
    }

    public function addEmail(string $email, bool $skipVerification = false)
    {
        //does nothing, null users don't have emails
    }

    public function removeEmail(string $email)
    {
        //does nothing, null users don't have emails
    }

    public function create() : bool
    {
        //does nothing
        return false;
    }

    public function update() : bool
    {
        //does nothing
        return false;
    }

    public function delete() : bool
    {
        //does nothing
        return false;
    }
}
