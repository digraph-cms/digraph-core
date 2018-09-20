<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\Simple;

use Destructr\DSO;
use Digraph\Users\UserInterface;

class SimpleUser extends DSO implements UserInterface
{
    public function identifier() : string
    {
        return $this->get('dso.id');
    }

    public function name() : string
    {
        return $this['name'];
    }

    public function email() : ?string
    {
        if (!is_array($this['email.verified'])) {
            return null;
        }
        return array_shift($this['email.verified']);
    }

    public function setPassword(string $password)
    {
        $this['password'] = [
            'hash' => password_hash($password),
            'time' => time()
        ];
    }

    public function checkPassword(string $password) : bool
    {
        return password_verify($password, $this['password.hash']);
    }

    /**
     * Adds an email to emails list, and verifies it if $skipVerificaiton is true
     * Makes an email the primary (first) email if it already exists
     */
    public function addEmail(string $email, bool $skipVerification = false)
    {
        $email = strtolower($email);
        if (in_array($email) || $skipVerification) {
            //push to front of verified emails
            $this->unshift($email, 'email.verified');
            $this['email.verified'] = array_unique($this['email.verified']);
        } else {
            $this['email.unverified.'.md5($email)] = [
                'addr' => $email,
                'token' => bin2hex(random_bytes(16)),
                'expires' => time()+86400
            ];
        }
    }

    public function removeEmail(string $email)
    {
        //does nothing, null users don't have emails
    }
}
