<?php

namespace DigraphCMS\UI;

use DigraphCMS\Context;
use DigraphCMS\HTML\A;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

class CallbackLink extends A
{
    protected static $idCounter = 0;
    protected $callback;

    /**
     * @param callable $callback
     * @param string|URL|null $href
     * @param string|null $target
     * @param string|null $frameTarget
     */
    public function __construct(callable $callback, $href = null, string $target = null, string $frameTarget = null)
    {
        parent::__construct($href, $target, $frameTarget);
        $this->callback = $callback;
        $this->setID('callback-link-' . static::$idCounter++);
    }

    public function href()
    {
        return new URL('&__callback-link=' . $this->id() . '&__csrf=' . Cookies::csrfToken());
    }

    public function toString(): string
    {
        if (Context::arg('__callback-link') == $this->id() && Context::arg('__csrf') == Cookies::csrfToken()) {
            call_user_func($this->callback);
            if (!($url = $this->href)) {
                $url = Context::url();
                $url->unsetArg('__callback-link');
                $url->unsetArg('__csrf');
            }
            throw new ArbitraryRedirectException($url);
        }
        return parent::toString();
    }
}
