<?php

namespace DigraphCMS\HTML;

use Exception;

class OL extends Tag
{
    protected $tag = 'ol';

    public function addChild($child)
    {
        if (!$child instanceof LI) throw new Exception('Only LI tags can be children of a list');
        return parent::addChild($child);
    }
}
