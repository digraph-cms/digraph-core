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
     * @return void
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
     * @return $this
     */
    public function sent()
    {
        return $this->where('`error` is not null');
    }

    /**
     * Add where clause to limit to non-sent emails only
     *
     * @return $this
     */
    public function notSent()
    {
        return $this->where('`error` is null');
    }

    /**
     * Add where clause to limit to errored
     *
     * @return $this
     */
    public function errored()
    {
        return $this->where('`error` is not null');
    }

    /**
     * Add where clause to limit to non-errored emails only
     *
     * @return $this
     */
    public function notErrored()
    {
        return $this->where('`error` is null');
    }
}
