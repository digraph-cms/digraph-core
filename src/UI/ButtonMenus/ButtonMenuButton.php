<?php

namespace DigraphCMS\UI\ButtonMenus;

use DigraphCMS\Context;
use DigraphCMS\HTML\A;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

class ButtonMenuButton extends A
{
    protected static $idCounter = 0;
    protected $callback;

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
        parent::__construct($url);
        $this->callback = $callback;
        $this->setID('buttonmenu-' . static::$idCounter++);
        $this->addClass('button');
        $this->addChild($label);
        foreach ($classes as $class) {
            $this->addClass($class);
        }
    }

    public function href()
    {
        return new URL('&__buttonmenu=' . $this->id() . '&__csrf=' . Cookies::csrfToken());
    }

    public function toString(): string
    {
        if (Context::arg('__buttonmenu') == $this->id() && Context::arg('__csrf') == Cookies::csrfToken()) {
            call_user_func($this->callback);
            if (!($url = $this->href)) {
                $url = Context::url();
                $url->unsetArg('__buttonmenu');
                $url->unsetArg('__csrf');
            }
            throw new ArbitraryRedirectException($url);
        }
        return parent::toString();
    }
}
