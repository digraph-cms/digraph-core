<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Content\Blocks\Blocks;
use DigraphCMS\UI\Theme;

class RichContent
{
    protected $value;
    protected $editorValue;
    protected $publicValue;

    public function __construct(string $value = null)
    {
        $this->setValue($value ?? '');
    }

    public static function load()
    {
        static $loaded = false;
        if (!$loaded) {
            $loaded = true;
        }
    }

    function setValue(string $value)
    {
        $this->editorValue = null;
        $this->value = $value;
    }

    function editorValue(): string
    {
        if ($this->editorValue === null) {
            $this->editorValue = $this->value;
            $this->editorValue = preg_replace_callback(
                '/<figure(.+?)data-trix-attachment="(.+?)"(.*?)>(.*?)<\/figure>/im',
                function (array $matches): string {
                    $json = json_decode(htmlspecialchars_decode($matches[2]), true);
                    $block = Blocks::get($json['uuid']);
                    return sprintf(
                        '<figure%sdata-trix-attachment="%s"%s>%s</figure>',
                        $matches[1],
                        htmlspecialchars(json_encode($block->array())),
                        $matches[3],
                        $block ? $block->html_editor() : "<div class='notification notification--error'>Block not found</div>"
                    );
                },
                $this->editorValue
            );
        }
        return $this->editorValue;
    }

    function publicValue(): string
    {
        if ($this->publicValue === null) {
            $this->publicValue = $this->value;
            $this->publicValue = preg_replace_callback(
                '/<figure(.+?)data-trix-attachment="(.+?)"(.*?)>(.*?)<\/figure>/ims',
                function (array $matches): string {
                    $json = json_decode(htmlspecialchars_decode($matches[2]), true);
                    $block = Blocks::get($json['uuid']);
                    return sprintf(
                        '<figure class="attachment attachment--content">%s</figure>',
                        $block ? $block->html_public() : "<div class='notification notification--error'>Block not found</div>"
                    );
                },
                $this->publicValue
            );
        }
        return $this->publicValue;
    }

    function __toString()
    {
        static::load();
        return '<div class="trix-content">' . $this->publicValue() . '</div>';
    }
}
