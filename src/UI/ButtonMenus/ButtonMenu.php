<?php

namespace DigraphCMS\UI\ButtonMenus;

use DigraphCMS\Context;
use DigraphCMS\Session\Cookies;

class ButtonMenu
{
    protected static $id = 0;
    protected $label, $myID;
    protected $csrf = false;
    /** @var ButtonMenuButton[] */
    protected $buttons = [];
    protected $target = '_frame';

    public function __construct(string $label = null, array $buttons = [])
    {
        $this->label = $label;
        $this->myID = static::$id++;
        foreach ($buttons as $button) {
            if ($button instanceof ButtonMenuButton) {
                $this->addButton($button);
            } else {
                @list($label, $callback, $classes) = $button;
                $this->newButton($label, $callback, $classes ?? []);
            }
        }
    }

    public function addButton(ButtonMenuButton $button)
    {
        $this->buttons[] = $button;
    }

    public function newButton(string $label, callable $callback, array $classes = []): ButtonMenuButton
    {
        return $this->buttons[] = new ButtonMenuButton($label, $callback, $classes);
    }

    public function id(): string
    {
        return 'buttonmenu_' . md5($this->myID);
    }

    public function csrf(bool $csrf = null): bool
    {
        if ($csrf !== null) {
            $this->csrf = $csrf;
            if ($this->csrf) {
                Cookies::require('csrf');
            }
        }
        return $this->csrf;
    }

    public function token()
    {
        if ($this->csrf) {
            return
                Cookies::get('csrf', $this->id()) ??
                Cookies::set('csrf', $this->id(), bin2hex(random_bytes(16)), false, true);
        } else {
            return md5(Context::url() . $_SERVER['HTTP_USER_AGENT']);
        }
    }

    public function execute()
    {
        if (@Context::request()->post()[$this->id()] == $this->token()) {
            foreach ($this->buttons as $button) {
                if (@$_POST[$button->id()]) {
                    $button->execute();
                }
            }
            Cookies::unset('csrf', $this->id(), true);
            Context::response()->redirect(Context::url());
        }
    }

    public function __toString()
    {
        ob_start();
        $this->execute();
        echo "<nav class='button-menu'><form method='POST' action='" . Context::url() . "' data-target='" . $this->target . "' id='" . $this->id() . "'>";
        echo "<div class='buttons'>";
        foreach ($this->buttons as $button) {
            echo $button;
        }
        echo "</div>";
        echo "<input type='hidden' name='" . $this->id() . "' value='" . $this->token() . "'>";
        echo "</form></nav>";
        return ob_get_clean();
    }
}
