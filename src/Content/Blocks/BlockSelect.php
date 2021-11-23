<?php

namespace DigraphCMS\Content\Blocks;

use DigraphCMS\DB\AbstractMappedSelect;

class BlockSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return Blocks::resultToBlock($row);
    }
}
