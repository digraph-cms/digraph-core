<?php

namespace DigraphCMS\HTML;

class Text extends Node
{
    protected $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function setContent(string $content)
    {
        $this->content = $content;
        return $this;
    }

    public function toString(): string
    {
        return $this->content;
    }
}
