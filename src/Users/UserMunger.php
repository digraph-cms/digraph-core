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
        $conf = $package->cms()->config['users.namespacing'];
        switch ($conf['mode']) {
            case 'auth':
                $package['request.namespace'] = 'auth/'.($users->id()?'true':'false');
                return;
            case 'id':
                $package['request.namespace'] = 'id/'.$users->userIdentifier();
                return;
            case 'groups':
                $package['request.namespace'] = 'groups/'.implode(',', $users->groups());
                return;
        }
    }

    protected function doConstruct($name)
    {
    }
}
