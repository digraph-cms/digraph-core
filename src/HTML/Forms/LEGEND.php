<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\Tag;

class LEGEND extends Tag
{
    protected $tag = 'legend';

    protected $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    /**
     * Set text of label
     *
     * @param string $text
     * @return $this
     */
    public function setText(string $text)
    {
        $this->text = $text;
        return $this;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function children(): array
    {
        return array_merge(
            [$this->text()],
            parent::children()
        );
    }
}
