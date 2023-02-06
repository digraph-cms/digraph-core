<?php

namespace DigraphCMS\RichMedia;

use ArrayIterator;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;

/**
 * @method ArrayIterator<AbstractRichMedia> getIterator()
 */
class RichMediaSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row): ?object
    {
        return RichMedia::resultToMedia($row);
    }
}
