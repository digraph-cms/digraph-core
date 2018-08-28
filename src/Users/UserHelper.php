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

    public function userID(string $set = null) : ?string
    {
        return $this->cms->helper('session')->userID($set);
    }

    public function userName()
    {
        if ($id = $this->userID()) {
            return @array_pop(explode('/', $id));
        }
        return null;
    }

    public function userGroups()
    {
        //TODO: implement groups
        return [];
    }
}
