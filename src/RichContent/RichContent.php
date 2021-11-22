<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\UI\Theme;

class RichContent
{
    protected $value;

    public function __construct(string $value = null)
    {
        $this->setValue($value ?? '');
    }

    public static function load()
    {
        static $loaded = false;
        if (!$loaded) {
            Theme::addBlockingPageCss('/core/trix/trix-content.css');
            $loaded = true;
        }
    }

    function setValue(string $value)
    {
        $this->value = $value;
    }

    function value(): string
    {
        return $this->value;
    }
}
