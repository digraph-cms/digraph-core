<?php

namespace DigraphCMS\RichMedia;

use DigraphCMS\DB\AbstractMappedSelect;

class RichMediaSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return RichMedia::resultToMedia($row);
    }
}
