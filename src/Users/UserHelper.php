<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\Helpers\AbstractHelper;

class UserHelper extends AbstractHelper
{
    protected $managers = [];

    public function validateIdentifier(string $identifier) : bool
    {
        if (preg_match('/[@]/', $identifier)) {
            return false;
        }
        return true;
    }

    public function getByEmail(string $email)
    {
        $out = [];
        foreach ($this->managers as $manager) {
            if ($user = $manager->getByEmail($email)) {
                $out[$user->identifier()] = $user;
            }
        }
        return $out;
    }

    public function signout()
    {
        $this->cms->helper('session')->deauthorize();
    }

    public function user(string $id = null) : ?UserInterface
    {
        if ($id === null) {
            $id = $this->id();
        }
        if (!$id) {
            return null;
        }
        $manager = $this->userManager($id);
        $identifier = $this->userIdentifier($id);
        if ($m = $this->manager($manager)) {
            return $m->getByIdentifier($identifier);
        }
        return null;
    }

    public function groups(string $id = null) : ?array
    {
        //load current id if null
        if (!$id) {
            $id = $this->id();
        }
        //return empty array if user is still null
        if (!$id) {
            return [];
        }
        //default list is empty
        $groups = [];
        //load groups from config
        if (isset($this->cms->config['groups.'.$id])) {
            $groups = $this->cms->config['groups.'.$id];
        }
        //TODO: make groups somehow configurable on the user side
        //return list
        return $groups;
    }

    public function id(string $set = null) : ?string
    {
        return $this->cms->helper('session')->userID($set);
    }

    public function userManager($id = null) : ?string
    {
        if ($id = $this->id()) {
            return @array_pop(explode('@', $id));
        }
        return null;
    }

    public function userIdentifier($id = null) : ?string
    {
        if (!$id == null) {
            $id = $this->id();
        }
        if ($id !== null) {
            return @array_shift(explode('@', $id));
        }
        return null;
    }

    public function manager($name = null) : ?Managers\UserManagerInterface
    {
        if (!$name) {
            $name = $this->cms->config['users.defaultmanager'];
        }
        if (!isset($this->managers[$name])) {
            if (isset($this->cms->config['users.managers.'.$name])) {
                $class = $this->cms->config['users.managers.'.$name.'.class'];
                $this->cms->log('Instantiating user manager '.$name.': '.$class);
                $this->managers[$name] = new $class($this->cms);
                $this->managers[$name]->name($name);
            }
        }
        return @$this->managers[$name];
    }

    public function signupAllowed(string $name) : ?bool
    {
        return $this->cms->config['users.managers.'.$name.'.signup'];
    }

    public function signinAllowed(string $name) : ?bool
    {
        return $this->cms->config['users.managers.'.$name.'.signin'];
    }
}
