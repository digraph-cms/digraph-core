<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers;

use Digraph\CMS;

interface UserManagerInterface
{
    public function __construct(CMS &$cms);
}
