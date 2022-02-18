<?php

namespace DigraphCMS\UI\MenuBar;

use DigraphCMS\Digraph;
use DigraphCMS\HTML\DIV;
use DigraphCMS\URL\URL;

class MenuItemFrame extends MenuItem
{
    protected $frameURL;

    /**
     * Construct with a given URL, label, and frame URL.
     *
     * @param URL|string|null $url
     * @param string $label
     * @param URL $frameURL
     */
    public function __construct($url, string $label, URL $frameURL)
    {
        $this->url = $url;
        $this->label = $label;
        $this->frameURL = $frameURL;
        $this->addChild(
            (new DIV)
                ->setID(Digraph::uuid())
                ->addClass('menuitem__frame')
                ->setData('id', 'main-content')
        );
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'data-dropdown-url' => $this->frameURL
            ]
        );
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'menuitem--dropdown--frame'
            ]
        );
    }
}
