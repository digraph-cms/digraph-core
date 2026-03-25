<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use DigraphCMS\Users\User;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;

/**
 * @method __construct(User $user)
 * @property User $value
 */
class UserCell extends AbstractCellWriter
{

    public function cell(): Cell
    {
        return FormulaCell::fromValue(
            sprintf(
                '=HYPERLINK("%s","%s")',
                str_replace('"', '""', $this->value->profile()),
                str_replace('"', '""', $this->value->name()),
            ),
        );
    }

}
