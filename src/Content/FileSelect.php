<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractMappedSelect;

class FilestoreSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return Filestore::resultToFile($row);
    }
}
