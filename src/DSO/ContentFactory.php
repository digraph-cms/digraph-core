<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\Factory;
use Digraph\CMS;

class ContentFactory extends Factory
{
    const ID_LENGTH = 8;
    protected $cms;

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

    public function class(array $data) : ?string
    {
        return Noun::class;
    }

    public function &cms(CMS &$set=null) : CMS
    {
        if ($set) {
            $this->cms = $set;
        }
        return $this->cms;
    }
}
