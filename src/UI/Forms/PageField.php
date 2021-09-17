<?php

namespace DigraphCMS\UI\Forms;

use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use Formward\Fields\AbstractTransformedInput;

class PageField extends AbstractTransformedInput
{
    function construct()
    {
        $this->addValidatorFunction(
            'page-exists',
            function (AbstractTransformedInput $field) {
                if (!Pages::get($field->submittedValue())) {
                    return "Value must be a valid page UUID or URL";
                }
                return true;
            }
        );
    }

    protected function transformValue($value)
    {
        if ($value) {
            return Pages::get($value);
        } else {
            return null;
        }
    }

    protected function unTransformValue($value)
    {
        if ($value instanceof Page) {
            return $value->uuid();
        } else {
            return null;
        }
    }
}
