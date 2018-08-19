<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Helpers;

use Digraph\CMS\CMS;

abstract class AbstractHelper implements HelperInterface
{
    protected $cms;

    public function __construct(CMS &$cms)
    {
        $this->cms = $cms;
    }
}
