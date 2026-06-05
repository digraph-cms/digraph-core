<?php

namespace DigraphCMS\HTML;

class DETAILS extends Tag
{

    protected $tag = 'details';

    public function __construct(
        public string $summary = 'read more',
        public bool $open = false,
    ) {}

    public function children(): array
    {
        $children = parent::children();
        array_unshift($children, sprintf('<summary>%s</summary>', $this->summary));
        return $children;
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        if ($this->open) {
            $attributes['open'] = null;
        }
        return $attributes;
    }

}
