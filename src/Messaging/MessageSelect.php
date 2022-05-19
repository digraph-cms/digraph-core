<?php

namespace DigraphCMS\Messaging;

use DigraphCMS\DB\AbstractObjectSelect;

/**
 * @method Message|null fetch()
 * @method Message[] fetchAll()
 */
class MessageSelect extends AbstractObjectSelect
{
    const OBJECT_CLASS = Message::class;

    /**
     * Add where clause to limit to archive messages only
     *
     * @return $this
     */
    public function archive()
    {
        return $this->where('`archived` = 1');
    }

    /**
     * Add where clause to limit to inbox messages only
     *
     * @return $this
     */
    public function inbox()
    {
        return $this->where('`archived` = 0');
    }

    /**
     * Add where clause to limit to read messages only
     *
     * @return $this
     */
    public function read()
    {
        return $this->where('`read` = 1');
    }

    /**
     * Add where clause to limit to unread messages only
     *
     * @return $this
     */
    public function unread()
    {
        return $this->where('`read` = 0');
    }
}
