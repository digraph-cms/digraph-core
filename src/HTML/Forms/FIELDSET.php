<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\Tag;

class FIELDSET extends Tag
{
    protected $tag = 'fieldset';
    protected $legend = null;

    public function __construct(string $label = null)
    {
        if ($label) {
            $this->legend = new LEGEND($label);
            $this->addChild($this->legend);
        }
    }

    public function toString(): string
    {
        return parent::toString();
    }
}
