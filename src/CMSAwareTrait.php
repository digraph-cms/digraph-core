<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph;

trait CMSAwareTrait
{
    protected $cms;

    public function &cms(CMS &$cms = null) : ?CMS
    {
        if ($cms !== null) {
            $this->cms = $cms;
        }
        return $this->cms;
    }
}
