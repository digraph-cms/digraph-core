<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Simple;

use Destructr\DSO;
use Digraph\Users\UserInterface;

class SimpleUser extends DSO implements UserInterface
{
    protected $managerName;

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
        return $this->identifier().'@'.$this->managerName;
    }

    public function identifier() : string
    {
        return $this->get('dso.id');
    }

    public function name(string $set = null) : string
    {
        if ($set) {
            $this['name'] = $set;
        }
        if ($this['name']) {
            return $this['name'];
        }
        return "Unnamed user ".$this['dso.id'];
    }

    public function email() : ?string
    {
        return $this['email.primary'];
    }

    public function addEmail(string $email, bool $force = false)
    {
        $email = strtolower($email);
        //force this to be primary/verified
        if ($force) {
            $this['email.primary'] = $email;
            $this['email.verified'] = true;
            unset($this['email.pending']);
            return;
        }
        //does nothing if this is already the primary email
        if ($email == $this['email.primary']) {
            return;
        }
        //make primary if there is no email, but verified is still false
        if (!$this['email.primary']) {
            $this['email.primary'] = $email;
            $this['email.verified'] = false;
        }
        //add as a pending email
        $this['email.pending'] = [
            'address' => $email,
            'token' => bin2hex(random_bytes(16)),
            'time' => time()
        ];
    }

    public function removeEmail(string $email)
    {
        $email = strtolower($email);
        if ($this['email.primary'] == $email) {
            unset($this['email.primary']);
        }
        if ($this['email.pending.address'] == $email) {
            unset($this['email.pending']);
        }
    }

    public function getEmailToken() : ?string
    {
        return $this['email.pending.token'];
    }

    public function checkEmailToken(string $token) : bool
    {
        if (!$this['email.pending.token']) {
            return false;
        }
        return $token == $this['email.pending.token'];
    }

    public function verifyEmail(string $email)
    {
        $email = strtolower($email);
        $this['email.primary'] = $email;
        $this['email.verified'] = true;
        unset($this['email.pending']);
    }

    public function pendingEmail() : ?string
    {
        return $this['email.pending.address'];
    }

    public function pendingEmailTime() : ?int
    {
        return $this['email.pending.time'];
    }

    public function setPassword(string $password)
    {
        $this['password'] = [
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'time' => time()
        ];
    }

    public function checkPassword(string $password) : bool
    {
        return password_verify($password, $this['password.hash']);
    }
}
