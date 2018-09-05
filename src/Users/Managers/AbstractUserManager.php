<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers;

use Digraph\CMS;

abstract class AbstractUserManager implements UserManagerInterface
{
    protected $cms;

    public function __construct(CMS &$cms)
    {
        $this->cms = $cms;
    }
}
