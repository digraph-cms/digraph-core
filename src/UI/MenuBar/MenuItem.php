<?php

namespace DigraphCMS\UI\MenuBar;

use DigraphCMS\Context;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\SPAN;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\URL\URL;

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

    public function label(): string
    {
        return $this->label;
    }

    public function url()
    {
        return $this->url instanceof URL ? clone $this->url : $this->url;
    }

    public function children(): array
    {
        $children = [];
        // set up link tag
        $a = (new A)
            ->addClass('menuitem__link')
            ->addChild($this->label)
            ->setAttribute('tabindex', '0');
        if ($this->url) {
            $a->setAttribute('href', $this->url);
        }
        $children[] = $a;
        // set up dropdown content if necessary
        if (parent::children()) {
            $d = (new DIV)
                ->addClass('menuitem__dropdown');
            foreach (parent::children() as $c) {
                $d->addChild($c);
            }
            $children[] = $d;
        }
        // merge and return
        return $children;
    }

    public function classes(): array
    {
        $classes = parent::classes();
        $classes[] = 'menuitem';
        // css class for styling dropdowns that contain children
        if (parent::children()) {
            $classes[] = 'menuitem--dropdown';
        }
        // classes for emphasizing current/current-parent links
        if ($this->url) {
            // first try a simple string comparison because it's fast
            if (str_replace('~', '', $this->url->__toString()) == str_replace('~', '', Context::url()->__toString())) {
                $classes[] = 'menuitem--current';
            } elseif ($this->url->path() != '/' && $this->url->path() != '/home/') {
                // try doing a substring comparison on URLs because it's almost as fast
                $path = str_replace('~', '', $this->url->path());
                if (substr(str_replace('~', '', Context::url()->path()), 0, strlen($path)) == $path) {
                    $classes[] = 'menuitem--current-parent';
                }
                // as a last resort generate the breadcrumb and try to find this URL in it
                else {
                    foreach (Breadcrumb::breadcrumb() as $parent) {
                        if (str_replace('~', '', $parent->path()) == $path) {
                            $classes[] = 'menuitem--current-parent';
                        }
                    }
                }
            }
        }
        return $classes;
    }
}
