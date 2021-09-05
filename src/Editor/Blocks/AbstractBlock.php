<?php

namespace DigraphCMS\Editor\Blocks;

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
