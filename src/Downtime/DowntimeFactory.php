<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Downtime;

use Digraph\DSO\DigraphFactory;

class DowntimeFactory extends DigraphFactory
{
    const ID_LENGTH = 16;
    protected $name = 'downtime';
    const LEGACYSCHEMA = false;

    protected $schema = [
        'dso.id' => [
            'name' => 'dso_id',
            'type' => 'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
            'primary' => true,
        ],
        'downtime.start' => [
            'name' => 'downtime_start',
            'type' => 'BIGINT',
            'index' => 'BTREE',
        ],
        'downtime.end' => [
            'name' => 'downtime_end',
            'type' => 'BIGINT',
            'index' => 'BTREE',
        ],
    ];
}
