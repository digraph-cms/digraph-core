<?php

namespace DigraphCMS\UI\ButtonMenus;

use DigraphCMS\HTML\DIV;
use DigraphCMS\URL\URL;

class ButtonMenu extends DIV
{
    /** @var DIV */
    protected $buttons;

    public function __construct(array|null $buttons = [])
    {
        $this->buttons = (new DIV)->addClass('button-menu__buttons');
        $this->addChild($this->buttons);
        $this->addClass('button-menu');
        foreach ($buttons ?? [] as $button) {
            if ($button instanceof ButtonMenuButton) {
                $this->addButton($button);
            } else {
                @list($label, $callback, $classes) = $button;
                $this->newButton($label, $callback, $classes ?? []);
            }
        }
    }

    public function setTarget(string $target)
    {
        $this->setData('target', $target);
        return $this;
    }

    public function addButton(ButtonMenuButton $button)
    {
        $this->buttons->addChild($button);
    }

    public function newButton(string $label, callable|URL $callback_or_url, array $classes = []): ButtonMenuButton
    {
        $button = new ButtonMenuButton($label, $callback_or_url, $classes);
        $this->addButton($button);
        return $button;
    }
}
