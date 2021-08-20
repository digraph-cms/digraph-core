<?php

namespace DigraphCMS\Users;

use DigraphCMS\DB\AbstractMappedSelect;

class UserSelect extends AbstractMappedSelect
{
    function doRowToObject(array $row): object
    {
        return Users::resultToUser($row);
    }
}
