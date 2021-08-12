<?php

namespace DigraphCMS\Forms;

use DigraphCMS\Content\Page;
use Formward\Fields\Input;

class Fields {
    public static function name(Page $page): Input
    {
        $field = new Input('Name');
        $field->default($page->name());
        $field->required(true);
        return $field;
    }
}