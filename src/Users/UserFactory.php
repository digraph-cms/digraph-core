<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\DSO\DigraphFactory;

class UserFactory extends DigraphFactory
{
    const ID_LENGTH = 10;
    protected $name = 'users';

    protected $virtualColumns = [
        'dso.id' => [
            'name'=>'dso_id',
            'type'=>'VARCHAR(16)',
            'index' => 'BTREE',
            'unique' => true,
            'primary' => true
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
        'email.primary' => [
            'name'=>'email_primary',
            'type'=>'VARCHAR(255)',
            'index'=>'BTREE',
            'unique'=>true
        ],
        'email.pending.address' => [
            'name'=>'email_pending',
            'type'=>'VARCHAR(255)',
            'index'=>'BTREE'
        ]
    ];
}
