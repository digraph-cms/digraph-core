<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractMappedSelect;

/**
 * @template T of AbstractPage
 * @extends AbstractMappedSelect<T>
 */
class PageSelect extends AbstractMappedSelect
{
    function doRowToObject(array $row): ?object
    {
        return Pages::resultToPage($row);
    }
}
