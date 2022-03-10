<?php

namespace DigraphCMS\Email;

use DigraphCMS\DB\AbstractMappedSelect;

class EmailSelect extends AbstractMappedSelect
{
    protected function doRowToObject(array $row)
    {
        return Emails::resultToEmail($row);
    }

    /**
     * Add where clause to limit to blocked
     *
     * @return $this
     */
    public function blocked()
    {
        return $this->where('`blocked` = 1');
    }

    /**
     * Add where clause to limit to non-blocked messages only
     *
     * @return $this
     */
    public function notBlocked()
    {
        return $this->where('`blocked` = 0');
    }
}
