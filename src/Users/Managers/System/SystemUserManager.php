<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers\System;

use Digraph\Users\Managers\Null\NullUserManager;

class SystemUserManager extends NullUserManager
{
    const USERCLASS = SystemUser::class;
    const MANAGER = 'system';
}
