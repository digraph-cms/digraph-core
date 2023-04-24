<?php

namespace DigraphCMS\HTML\Forms\Fields;

use DigraphCMS\HTML\Forms\CheckboxList;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Tag;

/**
 * @method CheckboxList input()
 */
class CheckboxListField extends Field
{
    protected $tag = 'fieldset';

    public function __construct(string $label, array $options = [])
    {
        parent::__construct($label, new CheckboxList($options));
        $this->addClass('checkbox-list-field');
    }

    public function field(string $key): ?CheckboxField
    {
        return $this->input()->field($key);
    }

    public function children(): array
    {
        return array_merge(
            [
                $this->label(),
                $this->tips(),
                $this->validationMessage(),
                $this->input(),
            ],
            $this->children
        );
    }
}
