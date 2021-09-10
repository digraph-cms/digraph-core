<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\Context;

abstract class AbstractBlock
{
    protected $data;

    abstract public static function load();
    abstract public static function jsClass(): ?string;
    abstract public function render(): string;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    protected function anchorLink(): string
    {
        return "<a href='" . Context::url() . "#" . $this->id() . "' class='referenceable-block-link' title='link to this block'>anchor</a>";
    }

    public function __toString()
    {
        return json_encode($this->data);
    }

    public function id(): string
    {
        return md5($this->data['id']);
    }

    public function data(): array
    {
        return $this->data['data'];
    }
}
