<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\Search;

class ContentFactory extends DigraphFactory
{
    const ID_LENGTH = 10;
    const TYPE = 'content';

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
        ]
    ];

    public function executeSearch(Search $search, array $params=[], $deleted=false) : array
    {
        $result = parent::executeSearch($search, $params, $deleted);
        //mark package as relying on results
        if ($package = $this->cms->package()) {
            foreach ($result as $n) {
                $package->cacheTagNoun($n);
            }
        }
        //return
        return $result;
    }
}
