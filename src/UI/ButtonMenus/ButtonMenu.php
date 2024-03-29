<?php

namespace DigraphCMS\UI\ButtonMenus;

use DigraphCMS\URL\URL;

class ButtonMenu
{
    protected $label, $myID;
    /** @var ButtonMenuButton[] */
    protected $buttons = [];
    protected $target = '_frame';

    public function __construct(string $label = null, array $buttons = [])
    {
        $this->label = $label;
        foreach ($buttons as $button) {
            if ($button instanceof ButtonMenuButton) {
                $this->addButton($button);
            } else {
                @list($label, $callback, $classes) = $button;
                $this->newButton($label, $callback, $classes ?? []);
            }
        }
    }

    /**
     * Set the data-target attribute
     *
     * @param string $target
     * @return static
     */
    public function setTarget(string $target)
    {
        $this->target = $target;
        return $this;
    }

    public function addButton(ButtonMenuButton $button)
    {
        $this->buttons[] = $button;
    }

    public function newButton(string $label, callable|URL $callback_or_url, array $classes = []): ButtonMenuButton
    {
        return $this->buttons[] = new ButtonMenuButton($label, $callback_or_url, $classes);
    }

    public function __toString()
    {
        $out = "<nav class='button-menu'>";
        $out .= "<div class='button-menu__buttons'>";
        foreach ($this->buttons as $button) {
            $out .= $button;
        }
        $out .= "</div>";
        $out .= "</nav>";
        return $out;
    }
}
