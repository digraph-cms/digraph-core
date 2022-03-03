<?php

namespace DigraphCMS\Messaging;

use DigraphCMS\DB\DB;

class Messages
{
    /**
     * Generate a select object for building queries
     *
     * @return MessageSelect
     */
    public static function select(): MessageSelect
    {
        return new MessageSelect(
            DB::query()->from('message')
        );
    }

    /**
     * Query to return unread inbox messages, used for notification dot and
     * user menu interface
     *
     * @return MessageSelect
     */
    public static function notify(): MessageSelect
    {
        return static::select()
            ->inbox()
            ->unread();
    }
}
