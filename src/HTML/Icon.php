<?php

namespace DigraphCMS\HTML;

use DigraphCMS\Config;

class Icon extends Tag
{
    protected $tag = 'i';
    protected $classes = ['icon'];
    protected $name = 'unspecified';
    protected $alt = 'unspecified icon';
    protected $type = 'icofont';
    protected $string = '&#xefcc;';
    protected $valid = false;

    const ICONS = [
        'undo' => ['string' => '&#xee0b;'],
        'redo' => ['string' => '&#xedfe;'],
        'link' => ['string' => '&#xef71;'],
        'close' => ['string' => '&#xec4f;'],
        'web' => ['string' => '&#xf028;'],
        'copy' => ['string' => '&#xec51;'],
        'block-left' => ['string' => '&#xea60;'],
        'options' => ['string' => '&#xefb0;'],
        'media' => ['string' => '&#xec9e;'],
        'page' => ['string' => '&#xefb2;'],
        'eye-blocked' => ['string' => '&#xef22;'],
        'heading' => ['string' => '&#xedf0;'],
        'bold' => ['string' => '&#xede3;'],
        'italic' => ['string' => '&#xedf2;'],
        'code' => ['string' => '&#xede6;'],
        'quote' => ['string' => '&#xefcd;'],
        'list-bullet' => ['string' => '&#xef75;'],
        'list-numbered' => ['string' => '&#xef76;'],
        'user-search' => ['string' => '&#xed1a;']
    ];

    public function __construct(string $name, string $alt = null)
    {
        $this->setIcon($name);
        if ($alt) {
            $this->setAlt($alt);
        }
    }

    public function setAlt(string $alt)
    {
        $this->alt = $alt;
        return $this;
    }

    public function setIcon($name)
    {
        $icon = Config::get("icons.$name");
        $icon = $icon ?? @static::ICONS[$name];
        if ($icon) {
            $this->name = $name;
            $this->alt = @$icon['alt'] ?? $name;
            $this->type = @$icon['type'] ?? $this->type;
            $this->string = $icon['string'] ?? '&#xefcc;';
            $this->valid = true;
        } else {
            $this->name = 'unknown';
            $this->alt = 'unknown icon "' . $name . '"';
            $this->type = 'icofont';
            $this->string = '&#xefcc;';
            $this->valid = false;
        }
        return $this;
    }

    public function children(): array
    {
        return [
            new Text($this->string)
        ];
    }

    public function classes(): array
    {
        $classes = parent::classes();
        $classes[] = 'icon--' . $this->type;
        if (!$this->valid) {
            $classes[] = 'icon--invalid';
        }
        return $classes;
    }
}
