<?php

namespace DigraphCMS\UI\Pagination;

class ColumnHeader
{
    protected static $idCounter = 0;
    protected $label;
    protected $id;

    public function __construct(string $label)
    {
        $this->label = $label;
        $this->id = 'c' . self::$idCounter++;
    }

    protected function classes(): array
    {
        return [];
    }

    public function colString(): string
    {
        return '<col id="' . $this->id . '" class="' . implode(' ', $this->classes()) . '"></col>';
    }

    protected function headerContent(): string
    {
        return $this->label;
    }

    public function __toString()
    {
        return sprintf(
            '<th id="th--' . $this->id . '" class="%s">%s</th>',
            implode(' ', $this->classes()),
            $this->headerContent()
        );
    }
}
