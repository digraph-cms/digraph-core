<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

abstract class AbstractCellWriter
{

    protected $value;

    public function __construct($value)
    {
        if (is_object($value))
            $value = clone $value;
        $this->value = $value;
    }

    public function cell(): Cell
    {
        $cell = Cell::fromValue($this->value);
        if (!$cell->style)
            $cell = $cell->withStyle(new Style());
        return $cell;
    }

}
