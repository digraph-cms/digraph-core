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
    protected function doRowToObject(array $row)
    {
        return RichMedia::resultToMedia($row);
    }
}
