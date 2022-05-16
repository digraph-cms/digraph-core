<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\Tag;

class FIELDSET extends Tag
{
    protected $tag = 'fieldset';
    protected $legend = null;
    protected $form;

    public function __construct(string $label = null)
    {
        if ($label) {
            $this->legend = new LEGEND($label);
            $this->addChild($this->legend);
        }
    }

    public function setForm(FormWrapper $form)
    {
        $this->form = $form;
        foreach ($this->children() as $child) {
            if (is_object($child) && method_exists($child, 'setForm')) $child->setForm($this->form);
        }
    }

    public function toString(): string
    {
        if ($this->form) $this->setForm($this->form);
        return parent::toString();
    }
}
