<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

abstract class AbstractCellWriter
{

    protected $value;

    protected string|null $fill;

    public function __construct($value)
    {
        if (is_object($value))
            $value = clone $value;
        $this->value = $value;
    }

    public function cell(): Cell
    {
        $cell = Cell::fromValue($this->value);
        $style = new Style();
        if ($this->fill)
            $style = $style->withBackgroundColor($this->fill);
        return $cell->withStyle($style);
    }

    public function fill(): string|null
    {
        return $this->fill;
    }

    public function setFill(string|null $fill)
    {
        if (!$fill)
            $this->fill = null;
        else {
            $fill = strtoupper($fill);
            if (!preg_match('/[0-9A-F]{6}/', $fill))
                throw new \Exception("Fill must be 6 hex digits i.e. CCCCCC");
            $this->fill = $fill;
        }
        return $this;
    }

}
