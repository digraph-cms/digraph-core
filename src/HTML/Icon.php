<?php

namespace DigraphCMS\HTML;

class Icon extends Tag
{
    protected $tag = 'i';
    protected $classes = ['icon'];
    protected $name = 'unknown';
    protected $alt = 'unknown icon';
    protected $source = 'icofont';
    protected $codepoint = '';
    protected $valid = false;

    public function __construct(string $name, string $alt = null)
    {
    }

    public function classes(): array
    {
        $classes = parent::classes();
        $classes[] = 'icon--' . $this->source;
        if (!$this->valid) {
            $classes[] = 'icon--invalid';
        }
        return $classes;
    }
}
