<?php

namespace DigraphCMS\UI\ButtonMenus;

use DigraphCMS\UI\CallbackLink;
use DigraphCMS\URL\URL;

class ButtonMenuButton extends CallbackLink
{
    public function __construct(string $label, callable|URL $callback_or_url, array $classes = [])
    {
        if ($callback_or_url instanceof URL) {
            $url = $callback_or_url;
            $callback = function () {
            };
        } else {
            $callback = $callback_or_url;
            $url = null;
        }
        parent::__construct($callback, $url);
        $this->addClass('button');
        $this->addChild($label);
        foreach ($classes as $class) {
            $this->addClass($class);
        }
    }
}
