<?php

namespace DigraphCMS\Editor\Blocks;

use DigraphCMS\Context;
use DigraphCMS\UI\Format;

abstract class AbstractBlock
{
    protected $data;

    abstract public static function load();
    abstract protected static function jsClass(): string;
    abstract public function render(): string;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected static function trim(string $string): string
    {
        return preg_replace('/(<br>)+$/', '', trim($string));
    }

    protected static function jsConfig(): array
    {
        return [];
    }

    protected static function shortcut(): ?string
    {
        return null;
    }

    public static function jsConfigString(): ?string
    {
        $class = static::jsClass();
        $config = static::jsConfig();
        $shortcut = static::shortcut();
        if (!$config && !$shortcut) {
            return $class;
        }
        $sections = array_filter([
            'class: ' . static::jsClass(),
            $config ? 'config: ' . Format::js_encode_object($config) : false,
            $shortcut ? 'shortcut: ' . Format::js_encode_object($shortcut) : false
        ]);
        return '{ ' . implode(', ', $sections) . ' }';
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
