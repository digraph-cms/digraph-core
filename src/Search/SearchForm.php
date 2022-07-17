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
        $this->button()->setText('search');
        $this->setAction(new URL('/~search/'));
        $this->token()->setDoNotUse(true);
        $this->addClass('search-form');
        $this->setMethod('get');

        $this->queryField = new INPUT('query');
        $this->queryField->addClass('search-form__query');
        $this->queryField->setAttribute('placeholder', 'Search this site');
        $this->addChild($this->queryField);

        if ($full && Search::availableModes()) {
            $this->modeField = new SELECT(Search::availableModes());
            $this->modeField->setID('mode');
            $this->addClass('search-form--full');
            $this->modeField->addClass('search-form__mode');
            $this->addChild($this->modeField);
        }
    }

    public function queryField(): INPUT
    {
        return $this->queryField;
    }

    public function modeField(): ?SELECT
    {
        return $this->modeField;
    }

    public function queryMode(): ?string
    {
        // return mode field value if it exists
        if ($this->modeField) return $this->modeField->value(true);
        // otherwise return either first available mode or null
        elseif ($modes = Search::availableModes()) return key($modes);
        else return null;
    }

    public function query(): string
    {
        return $this->queryField->value(true) ?? '';
    }
}
