<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;

class AutocompleteInput extends INPUT
{
    protected $endpoint, $valueCallback;

    public function __construct(string $id = null, URL $endpoint, callable $valueCallback = null)
    {
        parent::__construct($id);
        $this->valueCallback = $valueCallback;
        $this->setEndpoint($endpoint);
        $this->setAttribute('placeholder', 'type something to search');
        Cookies::csrfToken('autocomplete');
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes['data-autocomplete-source'] = $this->endpoint();
        $attributes['autocomplete'] = 'off';
        // unset normal value attribute because it's useless for this, instead
        // try to convert value into a card that can be rendered on the user side
        if ($value = $attributes['value']) {
            unset($attributes['value']);
            if ($this->valueCallback) {
                if ($value = call_user_func($this->valueCallback, $value)) {
                    $attributes['data-value'] = json_encode($value);
                }
            }
        }
        return $attributes;
    }

    /**
     * @param URL $endpoint
     * @return static
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
