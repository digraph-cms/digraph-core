<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Logging;

use Digraph\DSO\DigraphFactory;

class LogFactory extends DigraphFactory
{
    const ID_LENGTH = 16;
    protected $name = 'logging';

    protected $schema = [
        'dso.id' => [
            'name' => 'dso_id',
            'type' => 'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
            'primary' => true,
        ],
        'dso.type' => [
            'name' => 'dso_type',
            'type' => 'VARCHAR(30)',
            'index' => 'BTREE',
        ],
    ];

    /**
     * This should never ever ever change, because it allows versions of Digraph
     * from before Destructr had schema management to be updated.
     */
    const LEGACYSCHEMA = [
        'dso.id' => [
            'name' => 'dso_id',
            'type' => 'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
            'primary' => true,
        ],
        'dso.type' => [
            'name' => 'dso_type',
            'type' => 'VARCHAR(30)',
            'index' => 'BTREE',
        ],
    ];
}
