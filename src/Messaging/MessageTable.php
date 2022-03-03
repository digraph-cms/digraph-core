<?php

namespace DigraphCMS\Messaging;

use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;

class MessageTable extends QueryTable
{
    protected $showRecipient;

    public function __construct(MessageSelect $select, $showRecipient = false)
    {
        $this->showRecipient = $showRecipient;
        $columns = [
            new ColumnHeader('Message'),
            new ColumnHeader('From'),
            new QueryColumnHeader('Sent', 'time', $select),
            new ColumnHeader('')
        ];
        parent::__construct(
            $select,
            [$this, 'callback'],
            $columns
        );
    }

    public function callback(Message $message): array
    {
        return [
            sprintf('<a href="%s">%s</a>', $message->url(), $message->subject()),
            $message->sender() ?? '<em>system</em>',
            Format::datetime($message->time()),
            'TODO: controls'
        ];
    }
}
