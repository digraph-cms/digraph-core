<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\Tag;

class LABEL extends Tag
{
    protected $tag = 'label';

    protected $for;
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

    public function for(): ?InputInterface
    {
        return $this->for;
    }

    public function setFor(InputInterface $tag)
    {
        $this->for = $tag;
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        if ($this->for() && $this->for()->id()) {
            $attributes['for'] = $this->for()->id();
        }
        return $attributes;
    }
}
