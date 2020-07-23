<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\CMS;

abstract class AbstractHelper implements HelperInterface
{
    protected $cms;

    public function __construct(CMS $cms)
    {
        $this->cms = $cms;
        $this->construct();
    }

    public function construct()
    {
    }
}
