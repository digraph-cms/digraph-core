<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;

class LinkCell extends AbstractCellWriter
{

    protected string $url;

    public function __construct($value, $url)
    {
        parent::__construct($value);
        $this->url = $url;
    }

    public function cell(): Cell
    {
        return FormulaCell::fromValue(
            sprintf(
                '=HYPERLINK("%s","%s")',
                str_replace('"', '""', $this->url),
                str_replace('"', '""', $this->value),
            ),
        );
    }

}
