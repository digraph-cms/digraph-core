<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

class ContentFactory extends SystemFactory
{
    const ID_LENGTH = 8;
    const TYPE = 'content';

    protected $virtualColumns = [
        'dso.id' => [
            'name'=>'dso_id',
            'type'=>'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true
        ],
        'dso.type' => [
            'name'=>'dso_type',
            'type'=>'VARCHAR(30)',
            'index'=>'BTREE'
        ],
        'dso.deleted' => [
            'name'=>'dso_deleted',
            'type'=>'BIGINT',
            'index'=>'BTREE'
        ],
        'digraph.slug' => [
            'name'=>'digraph_slug',
            'type'=>'VARCHAR(100)',
            'index'=>'BTREE'
        ],
        'digraph.parent.0' => [
            'name'=>'digraph_parent_0',
            'type'=>'VARCHAR(100)',
            'index'=>'BTREE'
        ]
    ];
}
