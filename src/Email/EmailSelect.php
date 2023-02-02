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
     * Return unsent and un-errored emails, ordered by least recently created
     *
     * @return static
     */
    public function queue()
    {
        return $this
            ->notSent()
            ->notErrored()
            ->order('id ASC');
    }

    /**
     * Add where clause to limit to sent
     *
     * @return static
     */
    public function sent()
    {
        return $this->where('`sent` is not null');
    }

    /**
     * Add where clause to limit to non-sent emails only
     *
     * @return static
     */
    public function notSent()
    {
        return $this->where('`sent` is null');
    }

    /**
     * Add where clause to limit to errored
     *
     * @return static
     */
    public function errored()
    {
        return $this->where('`error` is not null');
    }

    /**
     * Add where clause to limit to non-errored emails only
     *
     * @return static
     */
    public function notErrored()
    {
        return $this->where('`error` is null');
    }
}
