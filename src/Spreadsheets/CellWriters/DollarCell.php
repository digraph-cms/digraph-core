<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

/**
 * @method __construct(float $amount)
 * @property float $value
 */
class DollarCell extends AbstractCellWriter
{

    public function cell(): \OpenSpout\Common\Entity\Cell
    {
        $cell = parent::cell();
        $style = $cell->style
            ->withFormat('"$"#,##0.00');
        return $cell->withStyle($style);
    }

}
