<?php

namespace DigraphCMS\Messaging;

use DigraphCMS\DataObjects\DSOFactory;

class MessageFactory extends DSOFactory
{
    const SCHEMA = [
        'time' => [
            'name' => 'time',
            'type' => 'BIGINT',
            'index' => 'BTREE',
        ],
        'sender' => [
            'name' => 'sender',
            'type' => 'VARCHAR(50)',
            'index' => 'BTREE',
        ],
        'recipient' => [
            'name' => 'recipient',
            'type' => 'VARCHAR(50)',
            'index' => 'BTREE',
        ],
        'archived' => [
            'name' => 'archived',
            'type' => 'TINYINT',
            'index' => 'BTREE',
        ],
        'read' => [
            'name' => 'read',
            'type' => 'TINYINT',
            'index' => 'BTREE',
        ]
    ];

    public function query(): MessageQuery
    {
        return new MessageQuery($this);
    }

    public function class(?array $data): ?string
    {
        return Message::class;
    }
}
