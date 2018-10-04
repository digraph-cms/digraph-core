<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

use \Digraph\CMS;

abstract class AbstractFilter implements FilterInterface
{
    protected $cms;

    public function __construct(CMS &$cms)
    {
        $this->cms = $cms;
    }
}
