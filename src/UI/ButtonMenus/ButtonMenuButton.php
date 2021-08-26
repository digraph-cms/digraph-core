<?php

namespace DigraphCMS\UI\ButtonMenus;

class ButtonMenuButton
{
    protected static $id = 1;
    protected $label, $callback, $classes;
    public function __construct(string $label, callable $callback, array $classes = [])
    {
        $this->label = $label;
        $this->callback = $callback;
        $this->classes = $classes;
        $this->myID = static::$id++;
    }

    public function id(): string
    {
        return 'button_' . $this->myID;
    }

    public function execute()
    {
        call_user_func($this->callback);
    }

    public function __toString()
    {
        $classes = $this->classes;
        $classes[] = 'button-menu-button';
        $classes = implode(' ', $classes);
        return "<input type='submit' name='" . $this->id() . "' value='" . $this->label . "' class='$classes'>";
    }
}
