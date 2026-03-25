<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use DigraphCMS\Content\Page;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;

/**
 * @method __construct(Page $page)
 * @property Page $value
 */
class PageCell extends AbstractCellWriter
{

    public function cell(): Cell
    {
        return FormulaCell::fromValue(
            sprintf(
                '=HYPERLINK("%s","%s")',
                str_replace('"', '""', $this->value->url()),
                str_replace('"', '""', $this->value->name()),
            ),
        );
    }

}
