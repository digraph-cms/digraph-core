<?php

namespace DigraphCMS\RichContent;

use DateTime;
use DigraphCMS\Context;
use DigraphCMS\Session\Session;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class RichContent
{
    protected $source, $html;
    protected $created, $created_by, $updated_links;

    /**
     * Construct using either a string or a complete array including metadata
     *
     * @param string|array $value
     */
    public function __construct($value)
    {
        if (is_array($value)) {
            $this->source = @$value['source'] ?? '';
            $this->created = @$value['created'] ?? time();
            $this->created_by = @$value['created_by'] ?? Session::user() ?? 'guest';
        } else {
            $this->source = $value ?? '';
            $this->created = time();
            $this->created_by = Session::user();
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
            "created_by" => $this->createdByUUID()
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
     * Return a last-modified date for this content, which includes the last
     * modified date of any referenced media, such as Rich Media or embeds.
     *
     * @return DateTime
     */
    public function updatedLinks(): DateTime
    {
        if (!$this->updated_links) {
            $this->buildHTML();
        }
        return (new DateTime)->setTimestamp($this->updated_links);
    }

    /**
     * Return the UUID of the user who modified this content
     *
     * @return string
     */
    public function createdByUUID(): string
    {
        return $this->created_by;
    }

    /**
     * Return the user who created/updated this piece of content
     *
     * @return User
     */
    public function createdBy(): User
    {
        return Users::user($this->created_by);
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
     * Also sets this Rich Content and a data field called RichContent_time into
     * Context, and reads RichContent_time out afterwards. This means that while
     * parsing ShortCodes and Markdown 
     *
     * @return void
     */
    protected function buildHTML()
    {
        Context::data('RichContent', $this);
        Context::data('RichContent_time', $this->created);
        $this->html = $this->parseSource($this->source());
        $this->updated_links = Context::data('RichContent_time');
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
