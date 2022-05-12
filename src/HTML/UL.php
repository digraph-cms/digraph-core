<?php

namespace DigraphCMS\HTML;

class UL extends Tag
{
    protected $tag = 'ul';

    public function addChild($child)
    {
        if (!$child instanceof LI) throw new Exception('Only LI tags can be children of a list');
        return parent::addChild($child);
    }
}
