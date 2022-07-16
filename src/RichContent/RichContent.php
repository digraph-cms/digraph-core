<?php

namespace DigraphCMS\RichContent;

use DateTime;
use DigraphCMS\Context;

class RichContent
{
    protected $source, $html;
    protected $created;

    /**
     * Construct using either a string or a complete array including metadata
     *
     * @param null|string|array $value
     */
    public function __construct($value)
    {
        if (is_array($value)) {
            $this->source = @$value['source'] ?? '';
            $this->created = @$value['created'] ?? time();
        } else {
            $this->source = $value ?? '';
            $this->created = time();
        }
    }

    /**
     * Return whether the given other content (provided as an object or array)
     * has the same source content as this object. By default only ignores
     * the user and date, because those get recreated from scratch when an
     * object gets constructed.
     *
     * @param RichContent|array|null $other
     * @return boolean
     */
    public function compare($other): bool
    {
        if (!$other) {
            return false;
        }
        if ($other instanceof RichContent) {
            $other = $other->array();
        }
        return $this->compareArray($other);
    }

    protected function compareArray(array $other): bool
    {
        return $other['source'] == $this->source();
    }

    /**
     * Convert into an array for storage
     *
     * @return array
     */
    public function array(): array
    {
        return [
            "source" => $this->source(),
            "created" => $this->created,
        ];
    }

    /**
     * Date this piece of content was created/updated
     *
     * @return DateTime
     */
    public function created(): DateTime
    {
        return (new DateTime)->setTimestamp($this->created);
    }

    /**
     * Get the editor-side value of this content
     *
     * @return string
     */
    public function source(): string
    {
        return $this->source;
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
            $this->buildHTML();
        }
        return $this->html;
    }

    /**
     * Do the heavy lifting of converting source value to final
     * HTML values. Shortcodes are parsed before Markdown, so that
     * Markdown can see them as HTML and not mess them up.
     *
     * @return void
     */
    protected function buildHTML()
    {
        Context::data('RichContent', $this);
        $this->html = $this->parseSource($this->source());
    }

    /**
     * Actually parse source into final HTML. This is outside buildHTML() so
     * that subclasses can parse the source in different ways.
     *
     * @param string $source
     * @return string
     */
    protected function parseSource(string $source): string
    {
        $html = ShortCodes::parse($source);
        $html = Markdown::parse($html);
        return $html;
    }

    public function __toString()
    {
        return $this->html();
    }
}
