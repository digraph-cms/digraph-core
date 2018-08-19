<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\DSO;

use Destructr\Factory;
use Digraph\CMS\CMS;

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
