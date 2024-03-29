<?php

namespace DigraphCMS\UI\ButtonMenus;

use DigraphCMS\Context;
use DigraphCMS\Exception;
use DigraphCMS\ExceptionLog;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

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

    public function id(): string
    {
        return 'buttonmenu_' . md5($this->myID);
    }

    public function csrf(bool $csrf = null): bool
    {
        if ($csrf !== null) {
            $this->csrf = $csrf;
        }
        return $this->csrf;
    }

    public function token()
    {
        if ($this->csrf) {
            return Cookies::csrfToken();
        } else {
            return md5(Context::url() . $_SERVER['HTTP_USER_AGENT']);
        }
    }

    public function tokenID()
    {
        return md5($this->id()) . "_token";
    }

    public function submitID()
    {
        return md5($this->id()) . "_submit";
    }

    public function execute()
    {
        if (@$_POST[$this->submitID()] != '1') {
            return;
        } elseif (@$_POST[$this->tokenID()] == $this->token()) {
            foreach ($this->buttons as $button) {
                if (@$_POST[$button->id()]) {
                    $button->execute();
                }
            }
            Context::response()->redirect(Context::url());
        } else {
            ExceptionLog::log(new Exception('Button menu token mismatch', [
                '$_POST[$this->tokenID()]' => $_POST[$this->id()],
                '$this->token()' => $this->token(),
            ]));
        }
    }

    public function __toString()
    {
        ob_start();
        $this->execute();
        echo "<nav class='button-menu'><form method='POST' action='" . Context::url() . "' data-target='" . $this->target . "' id='" . $this->id() . "'>";
        echo "<div class='button-menu__buttons'>";
        foreach ($this->buttons as $button) {
            echo $button;
        }
        echo "</div>";
        echo "<input type='hidden' name='" . $this->tokenID() . "' value='" . $this->token() . "'>";
        echo "<input type='hidden' name='" . $this->submitID() . "' value='1'>";
        echo "</form></nav>";
        return ob_get_clean();
    }
}
