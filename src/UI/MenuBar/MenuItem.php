<?php

namespace DigraphCMS\UI\MenuBar;

use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\SPAN;

class MenuItem extends SPAN
{
    protected $url, $label;

    /**
     * Undocumented function
     *
     * @param URL|string|null $url
     * @param string $label
     */
    public function __construct($url, string $label)
    {
        $this->url = $url;
        $this->label = $label;
    }

    public function children(): array
    {
        // set up link tag
        $a = (new A)
            ->addClass('menuitem__link')
            ->addChild($this->label);
        if ($this->url) {
            $a->setAttribute('href', $this->url);
        }
        $link = [$a];
        // set up dropdown content if necessary
        $dropdown = [];
        if (parent::children()) {
            $d = (new DIV)
                ->addClass('menuitem__dropdown');
            foreach (parent::children() as $c) {
                $d->addChild($d);
            }
            $dropdown[] = $d;
        }
        // merge and return
        return array_merge($link, $dropdown);
    }

    public function classes(): array
    {
        $classes = parent::classes();
        $classes[] = 'menuitem';
        if (parent::children()) {
            $classes[] = 'menuitem--dropdown';
        }
        return $classes;
    }
}
