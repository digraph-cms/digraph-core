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

    public function managerName(string $set = null): string
    {
        if ($set) {
            $this->managerName = $set;
        }
        return $this->managerName;
    }

    public function id(): string
    {
        return $this->identifier() . '@' . $this->managerName;
    }

    public function identifier(): string
    {
        return $this->get('dso.id');
    }

    public function name(string $set = null): string
    {
        if ($set) {
            $this['name'] = $set;
        }
        if ($this['name']) {
            return $this->factory->cms()->helper('filters')->sanitize($this['name']);
        }
        return "Unnamed user " . $this['dso.id'];
    }

    public function email(): ?string
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
            'time' => time(),
            'ip' => $_SERVER['REMOTE_ADDR']
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

    public function getEmailToken(): ?string
    {
        return $this['email.pending.token'];
    }

    public function checkEmailToken(string $token): bool
    {
        // no pending email, return false
        if (!$this['email.pending']) {
            return false;
        }
        // pending token has expired (1 week), return false
        if (time() > $this['email.pending.time'] + (86400 * 7)) {
            return false;
        }
        // return whether they match
        return $token == $this['email.pending.token'];
    }

    public function verifyEmail(string $email)
    {
        $email = strtolower($email);
        $this['email.primary'] = $email;
        $this['email.verified'] = true;
        unset($this['email.pending']);
    }

    public function pendingEmail(): ?string
    {
        return $this['email.pending.address'];
    }

    public function pendingEmailTime(): ?int
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

    public function checkPassword(string $password): bool
    {
        // check
        $matches = password_verify($password, $this['password.hash']);
        // re-set password if it needs rehashing
        if ($matches && password_needs_rehash($this['password.hash'], PASSWORD_DEFAULT)) {
            $this->setPassword($password);
            $this->update();
        }
        // return check value
        return $matches;
    }
}