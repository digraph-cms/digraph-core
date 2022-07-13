<?php

namespace DigraphCMS\HTML;

class IFRAME extends Tag
{
    protected $tag = 'iframe';
    protected $src;

    public function __construct(?string $src)
    {
        $this->src = $src;
    }

    public function attributes(): array
    {
        return array_merge(
            ['src' => $this->src],
            parent::attributes()
        );
    }

    public function children(): array
    {
        return [];
    }
}
