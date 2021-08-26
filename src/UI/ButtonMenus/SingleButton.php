<?php

namespace DigraphCMS\UI\ButtonMenus;

class SingleButton extends ButtonMenuButton
{
    protected $menu;
    protected $toStringState = 0;

    public function __toString()
    {
        if ($this->toStringState == 0) {
            $this->toStringState = 1;
            $this->menu = $this->menu ?? new ButtonMenu(null, [$this]);
            return $this->menu->__toString();
        } else {
            $this->toStringState = 0;
            return parent::__toString();
        }
    }
}
