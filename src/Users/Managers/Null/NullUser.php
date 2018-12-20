<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Null;

use Digraph\Users\UserInterface;
use Flatrr\FlatArrayTrait;

class NullUser implements UserInterface
{
    use FlatArrayTrait;

    protected $identifier;
    protected $managerName;

    public function __construct(string $identifier, string $manager = 'null')
    {
        $this->identifier = $identifier;
        $this->managerName = $manager;
    }

    public function __toString()
    {
        return $this->name();
    }

    public function managerName(string $set = null) : string
    {
        if ($set) {
            $this->managerName = $set;
        }
        return $this->managerName;
    }

    public function id() : string
    {
        return $this->identifier.'@'.$this->managerName;
    }

    public function identifier() : string
    {
        return $this->identifier;
    }

    public function name(string $set = null) : string
    {
        return 'Null user '.$this->id();
    }

    //retrieve current primary email address
    public function email() : ?string
    {
        // does nothing
        return null;
    }
    //add an email address -- does nothing if email is already added
    public function addEmail(string $email)
    {
        // does nothing
    }
    //remove current/pending use of an email address
    public function removeEmail(string $email)
    {
        // does nothing
    }
    //get the token associated with a pending email
    public function getEmailToken() : ?string
    {
        // does nothing
        return null;
    }
    //check if a token matches the pending email token
    public function checkEmailToken(string $token) : bool
    {
        //does nothing
        return false;
    }
    //make an email primary and verified
    public function verifyEmail(string $email)
    {
        //does nothing
    }
    //get a pending email address if there is one
    public function pendingEmail() : ?string
    {
        // does nothing
        return null;
    }
    //get the timestamp a pending email address was added
    public function pendingEmailTime() : ?int
    {
        // does nothing
        return null;
    }

    public function setPassword(string $password)
    {
        // does nothing
    }
    public function checkPassword(string $password) : bool
    {
        // does nothing
        return false;
    }
    public function insert() : bool
    {
        // does nothing
        return false;
    }
    public function update() : bool
    {
        // does nothing
        return false;
    }
    public function delete() : bool
    {
        // does nothing
        return false;
    }
}
