<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractMappedSelect;

class PageSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return Pages::resultToPage($row);
    }
}
