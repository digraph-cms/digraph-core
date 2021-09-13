<?php

namespace DigraphCMS\Users;

use DigraphCMS\DB\AbstractMappedSelect;

class UserSelect extends AbstractMappedSelect
{
    function doRowToObject(array $row)
    {
        return Users::resultToUser($row);
    }
}
