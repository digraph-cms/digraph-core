<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;

class AutocompleteInput extends INPUT
{
    protected $endpoint;

    public function __construct(string $id = null, URL $endpoint)
    {
        parent::__construct($id);
        $this->setEndpoint($endpoint);
        $this->setAttribute('placeholder','type something to search');
        Cookies::csrfToken('autocomplete');
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'data-autocomplete-source' => $this->endpoint(),
                'autocomplete' => 'off',
            ]
        );
    }

    /**
     * @param URL $endpoint
     * @return $this
     */
    protected function setEndpoint(URL $endpoint)
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    protected function endpoint(): URL
    {
        return $this->endpoint;
    }

    public function toString(): string
    {
        return Format::base64obfuscate(
            parent::toString(),
            'Autocomplete inputs require Javascript to function'
        );
    }
}
