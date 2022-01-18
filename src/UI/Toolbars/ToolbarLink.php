<?php

namespace DigraphCMS\UI\Toolbars;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Icon;
use DigraphCMS\HTML\Tag;
use DigraphCMS\HTML\Text;
use DigraphCMS\URL\URL;

class ToolbarLink extends Tag
{
    protected $tag = 'a';
    protected $text, $icon, $command, $url, $shortcut, $tooltip, $iconElement;

    public function __construct(string $text, string $icon, string $command = null, URL $url = null)
    {
        $this->setText($text);
        $this->setIcon($icon);
        $this->setCommand($command);
        $this->setUrl($url);
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

    public function setCommand(?string $command)
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

    public function command(): ?string
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
        $attributes['tabindex'] = '0';
        if ($this->command()) {
            $attributes['data-command'] = $this->command();
        }
        if ($this->url()) {
            $attributes['href'] = $this->url();
        }
        return $attributes;
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
