<?php

namespace DigraphCMS\UI;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

class ToggleButton extends Tag
{
    protected $tag = 'div';
    protected static $idCounter = 0;

    /** @var callable */
    protected $toggleOn, $toggleOff;

    public function __construct(
        protected bool $state,
        callable $toggleOn,
        callable $toggleOff,
        protected bool $targetTop = false
    ) {
        $this->toggleOn = $toggleOn;
        $this->toggleOff = $toggleOff;
        $this->setID('toggle-button-' . static::$idCounter++);
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            array_filter([
                'toggle-button',
                $this->state ? 'toggle-button--on' : 'toggle-button--off',
                $this->targetTop ? false : 'navigation-frame',
                $this->targetTop ? false : 'navigation-frame--stateless'
            ])
        );
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            array_filter([
                'data-target' => $this->targetTop ? false : 'frame'
            ])
        );
    }

    public function addChild($child)
    {
        throw new \Exception("Can't add children to a ToggleButton");
    }

    public function url_on(): URL
    {
        return new URL('&__toggle=' . $this->id() . '&__toggle_state=on&__csrf=' . Cookies::csrfToken());
    }

    public function url_off(): URL
    {
        return new URL('&__toggle=' . $this->id() . '&__toggle_state=off&__csrf=' . Cookies::csrfToken());
    }

    public function children(): array
    {
        return [
            $this->state
            ? '<span class="toggle-button__state">[ON]</span>'
            : '<span class="toggle-button__state">[OFF]</span>',
            ' ',
            $this->state
            ? '<a class="toggle-button__link" href="' . $this->url_off() . '">Turn OFF</a>'
            : '<a class="toggle-button__link" href="' . $this->url_on() . '">Turn ON</a>'
        ];
    }

    public function toString(): string
    {
        if (Context::arg('__toggle') == $this->id() && Context::arg('__csrf') == Cookies::csrfToken()) {
            if (Context::arg('__toggle_state') == 'on') {
                call_user_func($this->toggleOn);
            } elseif (Context::arg('__toggle_state') == 'off') {
                call_user_func($this->toggleOff);
            }
            $url = Context::url();
            $url->unsetArg('__toggle');
            $url->unsetArg('__toggle_state');
            $url->unsetArg('__csrf');
            throw new RedirectException($url);
        }
        return parent::toString();
    }
}