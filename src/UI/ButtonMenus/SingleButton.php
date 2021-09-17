<?php

namespace DigraphCMS\UI\ButtonMenus;

class SingleButton extends ButtonMenuButton
{
    protected $menu;
    protected $toStringState = 0;

    public function __construct(string $label, callable $callback, array $classes = [])
    {
        parent::__construct($label, $callback, $classes);
        $this->menu = new ButtonMenu(null, [$this]);
    }

    public function csrf(bool $csrf = null): bool
    {
        return $this->menu->csrf($csrf);
    }

    public function __toString()
    {
        // needs to toggle back and forth between returning own string and
        // parent menu's string, to avoid infinite loops
        if ($this->toStringState == 0) {
            $this->toStringState = 1;
            return $this->menu->__toString();
        } else {
            $this->toStringState = 0;
            return parent::__toString();
        }
    }
}
