<?php

namespace DigraphCMS\Search;

use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\URL\URL;

class SearchForm extends FormWrapper
{
    protected $queryField, $modeField;

    public function __construct(bool $full = false)
    {
        parent::__construct('search');
        $this->setAction(new URL('/~search/'));
        $this->token()->setDoNotUse(true);
        $this->addClass('search-form');
        $this->setMethod('get');

        $this->queryField = new INPUT('query');
        $this->queryField->addClass('search-form__query');
        $this->addChild($this->queryField);

        if ($full && Search::availableModes()) {
            $this->modeField = new SELECT(Search::availableModes());
            $this->addClass('search-form--full');
            $this->queryField->addClass('search-form__mode');
            $this->addChild($this->modeField);
        }
    }

    public function mode(): ?string
    {
        if ($this->modeField) return $this->modeField->value();
        return null;
    }

    public function query(): string
    {
        return $this->queryField->value();
    }
}
