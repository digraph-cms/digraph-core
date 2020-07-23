<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

use \Digraph\CMS;

abstract class AbstractFilter implements FilterInterface
{
    protected $cms;
    protected $context = null;

    public function __construct(CMS $cms)
    {
        $this->cms = $cms;
    }

    public function context(string $context = null) : ?string
    {
        if ($context !== null) {
            $this->context = $context;
        }
        return $this->context;
    }
}
