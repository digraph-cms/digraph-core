<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\Helpers\AbstractHelper;

class UserHelper extends AbstractHelper
{
    public function signout()
    {
        $this->cms->helper('session')->deauthorize();
    }

    public function id(string $set = null) : ?string
    {
        return $this->cms->helper('session')->userID($set);
    }

    public function username()
    {
        if ($id = $this->id()) {
            return @array_pop(explode('/', $id));
        }
        return null;
    }

    public function groups()
    {
        //TODO: implement groups
        return ['users'];
    }
}
