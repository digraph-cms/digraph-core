<?php

namespace DigraphCMS\UI\Toolbars;

use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Icon;
use DigraphCMS\HTML\Tag;
use DigraphCMS\HTML\Text;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;

class ToolbarLink extends Tag
{
    protected $tag = 'a';
    protected $text, $icon, $command, $url, $shortcut, $toolTip, $iconElement;
    protected static $idCounter = 0;

    /**
     * Undocumented function
     *
     * @param string $text
     * @param string $icon
     * @param null|string|callable $command
     * @param URL|null $url
     */
    public function __construct(string $text, string $icon, $command = null, URL $url = null, string $id = null)
    {
        $this->setText($text);
        $this->setIcon($icon);
        $this->setCommand($command);
        $this->setUrl($url);
        $this->setID($id ?? 'toolbarlink' . static::$idCounter++);
    }

    public function toString(): string
    {
        if (is_callable($this->command())) {
            if (Context::arg('__tblink') == $this->id()) {
                if (Context::arg('__tblinkcsrf') == Cookies::csrfToken('links')) {
                    // execute callback
                    call_user_func($this->command());
                    // redirect either back to original URL or to specified URL
                    $afterURL = $this->url();
                    if (!$afterURL) {
                        $afterURL = Context::url();
                        $afterURL->unsetArg('__tblink');
                        $afterURL->unsetArg('__tblinkcsrf');
                    }
                    throw new RedirectException($afterURL);
                }
            }
        }
        return parent::toString();
    }

    public function shortcut(): ?string
    {
        return $this->shortcut;
    }

    public function setShortcut(?string $shortcut)
    {
        $this->toolTip = null;
        $this->shortcut = $shortcut;
        return $this;
    }

    public function setText(string $text)
    {
        $this->text = $text;
        return $this;
    }

    public function setIcon(string $icon)
    {
        $this->iconElement = null;
        $this->icon = $icon;
        return $this;
    }

    /**
     * Set command to either a string or a callback. Strings will be set into
     * the HTML as data-command. If a callable is given here the link will 
     * render with a token URL that will execute the callback when visited,
     * similar to a ButtonMenu.
     *
     * @param null|string|callable $command
     * @return static
     */
    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }

    public function setUrl(?URL $url)
    {
        $this->url = $url;
        return $this;
    }

    public function text(): string
    {
        $this->toolTip = null;
        return $this->text;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    /**
     * The command to be executed by this toolbar button
     *
     * @return null|string|callable
     */
    public function command()
    {
        return $this->command;
    }

    public function url(): ?URL
    {
        return $this->url;
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes['tabindex'] = '-1';
        if ($this->url()) {
            $attributes['href'] = $this->url();
        }
        if ($command = $this->command()) {
            if (is_string($command)) {
                // set command string into HTML, presumably for JS use
                $attributes['data-command'] = $this->command();
            } else {
                // set href to special token URL
                $attributes['href'] = $this->executionURL();
                $attributes['rel'] = 'nofollow';
            }
        }
        return $attributes;
    }

    public function executionURL(): URL
    {
        $url = Context::url();
        $url->arg('__tblink', $this->id());
        $url->arg('__tblinkcsrf', Cookies::csrfToken('links'));
        return $url;
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'toolbar__button'
            ]
        );
    }

    public function iconElement(): Icon
    {
        if (!$this->iconElement) {
            $this->iconElement = new Icon($this->icon(), 'icon');
        }
        return $this->iconElement;
    }

    public function toolTip(): DIV
    {
        if (!$this->toolTip) {
            $this->toolTip = (new DIV())
                ->addClass('toolbar__button__tooltip')
                ->addChild(new Text($this->text));
            if ($this->shortcut()) {
                $this->toolTip()
                    ->addChild("<div class='toolbar__button__tooltip__shortcut'>" . $this->shortcut() . "</div>");
            }
        }
        return $this->toolTip;
    }

    public function children(): array
    {
        return array_merge(
            [
                $this->iconElement(),
                $this->toolTip()
            ],
            parent::children()
        );
    }
}
