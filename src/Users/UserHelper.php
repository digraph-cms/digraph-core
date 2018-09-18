<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\Helpers\AbstractHelper;

class UserHelper extends AbstractHelper
{
    protected $managers = [];

    public function signout()
    {
        $this->cms->helper('session')->deauthorize();
    }

    public function id(string $set = null) : ?string
    {
        return $this->cms->helper('session')->userID($set);
    }

    public function userManager() : ?string
    {
        if ($id = $this->id()) {
            return @array_shift(explode('/', $id));
        }
        return null;
    }

    public function username() : ?string
    {
        if ($id = $this->id()) {
            return @array_pop(explode('/', $id));
        }
        return null;
    }

    public function groups() : array
    {
        //TODO: implement groups
        return ['users'];
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
