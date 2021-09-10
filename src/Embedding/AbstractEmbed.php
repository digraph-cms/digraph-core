<?php

namespace DigraphCMS\Embedding;

use DigraphCMS\Config;
use HtmlObjectStrings\GenericTag;

abstract class AbstractEmbed
{
    protected $alt, $caption, $credit, $id;
    protected $additionalClasses = [];

    abstract protected function html(): string;
    abstract public function classes(): array;
    abstract public function aspectRatio(): float;
    abstract public function srcHash(): string;

    public function color(): ?string
    {
        return null;
    }

    public function addClass(string $class)
    {
        $this->additionalClasses[] = $class;
    }

    public function setID(string $id)
    {
        $this->id = $id;
    }

    public function height(): ?int
    {
        return null;
    }

    public function width(): ?int
    {
        return null;
    }

    public function alt(string $set = null): ?string
    {
        if ($set !== null) {
            $this->alt = $set;
        }
        return $this->alt;
    }

    public function caption(string $set = null): ?string
    {
        if ($set !== null) {
            $this->caption = $set;
        }
        return $this->caption;
    }

    public function credit(string $set = null): ?string
    {
        if ($set !== null) {
            $this->credit = $set;
        }
        return $this->credit;
    }

    public function __toString()
    {
        $classes = ['media-container'];
        $classes = array_merge($classes, $this->classes());
        $container = new GenericTag();
        $container->tag = 'div';
        $wrapper = new GenericTag();
        $wrapper->tag = 'div';
        $wrapper->addClass('media-wrapper');
        $css = ['container' => [], 'wrapper' => []];
        foreach ($classes as $class) {
            $container->addClass($class);
        }
        foreach ($this->additionalClasses as $class) {
            $container->addClass($class);
        }
        if ($this->id) {
            $container->attr('id', $this->id);
        }
        if ($this->width()) {
            $css['wrapper']['max-width'] = $this->width() . 'px';
        }
        if ($this->aspectRatio()) {
            $css['wrapper']['padding-bottom'] = $this->aspectRatio() * 100 . '%';
            $css['container']['max-width'] = (Config::get('embedding.max-height') / $this->aspectRatio()) . 'vh';
        } else {
            $container->addClass('fluid-height');
        }
        foreach ($css as $e => $r) {
            foreach ($r as $k => $v) {
                $css[$e][$k] = "$k:$v";
            }
        }
        $container->attr('style', implode(';', $css['container']));
        $wrapper->attr('style', implode(';', $css['wrapper']));
        $content = new GenericTag();
        $content->tag = 'div';
        $content->addClass('media-content');
        $content->content = $this->html();
        // add background color
        if ($this->color()) {
            $content->attr('style', 'background-color:' . $this->color());
        }
        // assemble the whole thing
        $wrapper->content = $content;
        if ($this->credit()) {
            $wrapper .= '<div class="media-credit">' . $this->credit() . '</div>';
        }
        if ($this->caption()) {
            $wrapper .= '<div class="media-caption">' . $this->caption() . '</div>';
        }
        if ($this->width()) {
            $container->content = "<div class=\"media-wrapper-maxwidth\" style=\"max-width:" . $this->width() . "px\">" . $wrapper . "</div>";
        } else {
            $container->content = $wrapper;
        }
        return '<!-- theme_package:library/media-embedding -->' . $container->__toString();
    }
}
