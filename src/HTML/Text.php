<?php

namespace DigraphCMS\HTML;

class Text extends Node
{
    protected $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function toString(): string
    {
        return $this->content;
    }
}
