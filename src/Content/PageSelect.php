<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractMappedSelect;

class PageSelect extends AbstractMappedSelect
{
    function doRowToObject(array $row): ?object
    {
        return Pages::resultToPage($row);
    }
}
