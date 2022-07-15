<?php

namespace DigraphCMS\UI\Sidebar;

class SidebarEvent
{
    protected $blocks;

    public function __construct(array &$blocks)
    {
        $this->blocks = &$blocks;
    }

    public function add($block)
    {
        $this->blocks[] = $block;
    }
}
