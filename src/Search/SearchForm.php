<?php

namespace DigraphCMS\Search;

use DigraphCMS\HTML\DIV;
use DigraphCMS\UI\Templates;

class SearchForm extends DIV
{
    public function __construct()
    {
        $this->addClass('search-form-wrapper');
    }

    public function formHTML()
    {
        return Templates::render('search/form.php');
    }

    public function children(): array
    {
        return array_merge(
            [$this->formHTML()],
            parent::children()
        );
    }
}
