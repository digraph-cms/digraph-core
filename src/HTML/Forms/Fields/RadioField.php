<?php
namespace DigraphCMS\HTML\Forms\Fields;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Radio;
use DigraphCMS\HTML\Forms\Field;

class RadioField extends Field
{
    public function __construct(string $label, string $id, string $key)
    {
        parent::__construct($label,new Radio($id,$key));
    }

    public function children(): array
    {
        $children = array_merge(
            [
                $this->input(),
                $this->label()
            ],
            DIV::children()
        );
        if ($this->submitted()) {
            $children[] = $this->validationMessage();
        }
        $children[] = $this->tips();
        return $children;
    }
}