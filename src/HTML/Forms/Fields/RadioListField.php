<?php

namespace DigraphCMS\HTML\Forms\Fields;

use DigraphCMS\HTML\Forms\RadioList;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Tag;

/**
 * @method RadioList input()
 */
class RadioListField extends Field
{
    protected $tag = 'fieldset';

    public function __construct(string $label, array $options = [])
    {
        parent::__construct($label, new RadioList($options));
        $this->addClass('radio-list-field');
    }

    public function field(string $key): ?RadioField
    {
        return $this->input()->field($key);
    }

    public function children(): array
    {
        $children = array_merge(
            [
                $this->label(),
                $this->tips(),
                $this->validationMessage(),
                $this->input(),
            ],
            Tag::children()
        );
        return $children;
    }
}
