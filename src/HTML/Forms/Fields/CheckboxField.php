<?php
namespace DigraphCMS\HTML\Forms\Fields;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Checkbox;
use DigraphCMS\HTML\Forms\Field;

class CheckboxField extends Field
{
    public function __construct(string $label)
    {
        parent::__construct($label,new Checkbox());
    }

    public function children(): array
    {
        $children = array_merge(
            [
                $this->input(),
                $this->label()
            ],
            $this->children
        );
        if ($this->submitted()) {
            $children[] = $this->validationMessage();
        }
        $children[] = $this->tips();
        return $children;
    }
}