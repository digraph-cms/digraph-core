<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\Mungers\AbstractMunger;

class UserMunger extends AbstractMunger
{
    protected function doMunge($package)
    {
        $users = $package->cms()->helper('users');
        if (!$users->id()) {
            //if user isn't signed in, we're done
            return;
        }
        //note that cache namespacing is always done by user identifier
        if($package->cms()->config['users.namespacing.enabled']) {
            // namespace by user
            $namespace = 'user/'.crc32($users->id());
        }else {
            // namespace by groups
            $namespace = 'group/' . crc32(serialize($users->groups()));
        }
        $package['request.namespace'] = $namespace;
    }

    protected function doConstruct($name)
    {
    }
}
