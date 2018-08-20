<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\Factory;
use Digraph\CMS;

class ContentFactory extends Factory
{
    protected $cms;

    public function class(array $data) : ?string
    {
        return Noun::class;
    }

    public function &cms(CMS &$set=null) : CMS
    {
        if ($set) {
            $this->cms = $set;
        }
        return $this->cms;
    }
}
