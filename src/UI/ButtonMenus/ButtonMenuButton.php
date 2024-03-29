<?php

namespace DigraphCMS\UI\ButtonMenus;

use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;

class ButtonMenuButton
{
    protected static $id = 1;
    protected $myID, $label, $callback_or_url, $classes;

    public function __construct(string $label, callable|URL $callback_or_url, array $classes = [])
    {
        $this->label = $label;
        $this->callback_or_url = $callback_or_url;
        $this->classes = $classes;
        $this->myID = static::$id++;
    }

    public function id(): string
    {
        return 'button_' . $this->myID;
    }

    public function execute()
    {
        if ($this->callback_or_url instanceof URL) {
            throw new RedirectException($this->callback_or_url);
        } else {
            call_user_func($this->callback_or_url);
        }
    }

    public function __toString()
    {
        $classes = $this->classes;
        $classes[] = 'button-menu-button';
        $classes = implode(' ', $classes);
        return "<input type='submit' name='" . $this->id() . "' value='" . $this->label . "' class='$classes'>";
    }
}
