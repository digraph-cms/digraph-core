<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\GroupSources;

use Digraph\CMS;

abstract class AbstractGroupSource implements GroupSourceInterface
{
    protected $cms;

    public function __construct(CMS &$cms)
    {
        $this->cms = $cms;
    }
}
