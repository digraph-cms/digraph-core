<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Content\Blocks\Blocks;
use DigraphCMS\UI\Theme;
use ParsedownExtra;

class RichContent
{
    protected $value;
    protected $html;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    /**
     * Set the editor-side value of this content
     *
     * @param string $value
     * @return $this
     */
    public function setValue(string $value)
    {
        $this->value = $value;
        $this->html = null;
        return $this;
    }

    /**
     * Get the editor-side value of this content
     *
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get the processed HTML value of this content, which can be
     * used as public content.
     *
     * @return string
     */
    public function html(): string
    {
        if ($this->html === null) {
            $this->html = $this->buildHTML($this->value);
        }
        return $this->html;
    }

    /**
     * Do the heavy lifting of converting source value to final
     * HTML values. Shortcodes are parsed before Markdown, so that
     * Markdown can see them as HTML and not mess them up.
     *
     * @param string $source
     * @return string
     */
    protected function buildHTML(string $source): string
    {
        $html = ShortCodes::parse($source);
        $html = static::parsedown()->text($html);
        return $html;
    }

    protected static function parsedown(): ParsedownExtra
    {
        static $parsedown;
        if (!$parsedown) {
            $parsedown = new ParsedownExtra();
        }
        return $parsedown;
    }

    public function __toString()
    {
        return $this->html();
    }
}
