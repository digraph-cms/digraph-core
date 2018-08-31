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
                $package['request.namespace'] = 'id/'.$users->id();
                return;
            case 'username':
                $package['request.namespace'] = 'username/'.$users->username();
                return;
            case 'groups':
                $groups = $users->groups();
                if ($ignore = @$conf['ignore']) {
                    $groups = array_filter(
                        $groups,
                        function ($e) use ($ignore) {
                            return !in_array($e, $ignore);
                        }
                    );
                }
                $package['request.namespace'] = 'groups/'.implode(';', $groups);
                return;
        }
    }

    protected function doConstruct($name)
    {
    }
}
