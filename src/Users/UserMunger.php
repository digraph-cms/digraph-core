<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\Mungers\AbstractMunger;

class UserMunger extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        $users = $package->cms()->helper('users');
        if (!$users->id()) {
            //if user isn't signed in, we're done
            return;
        }
        //note that cache namespacing is always done by user identifier
        $package['request.namespace'] = 'id/'.$users->userIdentifier();
    }

    protected function doConstruct($name)
    {
    }
}
