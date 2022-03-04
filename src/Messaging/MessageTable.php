<?php

namespace DigraphCMS\Messaging;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Icon;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Toolbars\ToolbarLink;

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
        $toolbar = (new DIV);
        // ->addClass('toolbar');
        if ($message->archived()) {
            $toolbar->addChild(
                (new ToolbarLink(
                    'Move to inbox',
                    'move-to-inbox',
                    function () use ($message) {
                        $message->setArchived(false);
                        $message->update();
                    },
                    null,
                    $message->uuid() . 'inbox'
                ))->setData('target', '_frame')
            );
        } else {
            $toolbar->addChild(
                (new ToolbarLink(
                    'Move to archive',
                    'archive',
                    function () use ($message) {
                        $message->setArchived(true);
                        $message->update();
                    },
                    null,
                    $message->uuid() . 'archive'
                ))->setData('target', '_frame')
            );
        }
        if ($message->read()) {
            $toolbar->addChild(
                (new ToolbarLink(
                    'Mark as unread',
                    'mark-unread',
                    function () use ($message) {
                        $message->setRead(false);
                        $message->update();
                    },
                    null,
                    $message->uuid() . 'unread'
                ))->setData('target', '_frame')
            );
        } else {
            $toolbar->addChild(
                (new ToolbarLink(
                    'Mark as read',
                    'mark-read',
                    function () use ($message) {
                        $message->setRead(true);
                        $message->update();
                    },
                    null,
                    $message->uuid() . 'read'
                ))->setData('target', '_frame')
            );
        }
        return [
            sprintf(
                '<a href="%s">%s%s%s</a>',
                $message->url(),
                $message->important() ? new Icon('important') : '',
                $message->sensitive() ? new Icon('secure') : '',
                $message->subject()
            ),
            $message->sender() ?? '<em>system</em>',
            Format::datetime($message->time()),
            $toolbar
        ];
    }
}
