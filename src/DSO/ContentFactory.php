<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\Search;

class ContentFactory extends DigraphFactory
{
    const ID_LENGTH = 8;
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
        ],
        'digraph.slug' => [
            'name'=>'digraph_slug',
            'type'=>'VARCHAR(100)',
            'index'=>'BTREE'
        ]
    ];

    protected function publishedClause()
    {
        $clause = implode(' AND ', [
            '(${digraph.published.start} is null OR ${digraph.published.start} <= :digraph_current_time)',
            '(${digraph.published.end} is null OR ${digraph.published.end} >= :digraph_current_time)',
            '(${digraph.published.force} <> "unpublished" OR ${digraph.published.force} is null)'
        ]);
        $clause = '(${digraph.published} is null OR ${digraph.published.force} = "published" OR ('.$clause.'))';
        return $clause;
    }

    public function executeSearch(Search $search, array $params=[], $deleted=false) : array
    {
        //if this user has permission to view unpublished info, just pass through
        //to parent executeSearch, because publication status is moot to them
        if ($this->cms->helper('permissions')->check('unpublished/view', 'content')) {
            $result = parent::executeSearch($search, $params, $deleted);
        } else {
            //add clause to search to enforce publication rules
            if ($where = $search->where()) {
                //add publication rule if it isn't already in the where clause
                if (strpos($where, '${digraph.published}') === false) {
                    $search->where('('.$where.') AND ${digraph.published}');
                }
            } else {
                //make published clause the entire where clause if there isn't anything
                $search->where('${digraph.published}');
            }
            //expand ${digraph.published} to a more complex clause
            $where = $search->where();
            // var_dump($where);
            if (strpos($where, '${digraph.published}') !== false) {
                $where = str_replace('${digraph.published}', $this->publishedClause(), $where);
                if (!isset($params[':digraph_current_time'])) {
                    $params[':digraph_current_time'] = time();
                }
                $search->where($where);
            }
            //call parent
            $result = parent::executeSearch($search, $params, $deleted);
        }
        //mark package as relying on results
        if ($package = $this->cms->package()) {
            foreach ($result as $n) {
                $package->cacheTag($n['dso.id']);
            }
        }
        //return
        return $result;
    }
}
