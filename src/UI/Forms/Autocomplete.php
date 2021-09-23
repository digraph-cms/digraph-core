<?php

namespace DigraphCMS\UI\Forms;

use DigraphCMS\Content\Pages;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Theme;
use Formward\Fields\Input;

class Autocomplete extends Input
{
    protected $ajaxSource, $cardCallback;

    protected static function load()
    {
        static $loaded = false;
        if (!$loaded) {
            Cookies::csrfToken('autocomplete');
            Theme::addBlockingPageJs('/autocomplete/autocomplete.js');
            Theme::addInternalPageCss('/autocomplete/autocomplete.css');
            $loaded = true;
        }
    }

    /**
     * URL of Ajax endpoint. Should accept the user's string query through the
     * "query" field, and return a JSON array of objects containing a "value"
     * field that will be the outputted value of the field, and an "html" field
     * that will be displayed to users in the results list.
     *
     * @param string|null $ajaxSource
     * @return string
     */
    public function ajaxSource(string $ajaxSource = null): string
    {
        $this->ajaxSource = $ajaxSource ?? $this->ajaxSource;
        return $this->ajaxSource;
    }

    /**
     * Callback to turn a given value into a displayable card. Should accept a
     * single string argument for the value, and return a string of the display
     * card content or null if nothing is found.
     *
     * @param callable $cardCallback
     * @return callable
     */
    public function cardCallback(callable $cardCallback): callable
    {
        $this->cardCallback = $cardCallback ?? $this->cardCallback;
        return $this->cardCallback;
    }

    /**
     * Overrides the default __toString to disable field if javascript is not
     * executing in the browser.
     *
     * @return string
     */
    public function __toString()
    {
        static::load();
        $this->attributes['autocomplete'] = 'off';
        $this->attributes['data-autocomplete-source'] = $this->ajaxSource();
        if ($this->value() && $page = Pages::get($this->value())) {
            $this->attributes['data-value'] = base64_encode(json_encode([
                'html' => Dispatcher::firstValue('onPageAutocompleteCard', [$page, null]),
                'value' => $this->value()
            ]));
        }
        return Format::base64obfuscate(parent::__toString(), 'Javascript is required to use autocomplete fields');
    }
}
