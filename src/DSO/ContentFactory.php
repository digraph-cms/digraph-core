<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\Factory;
use Digraph\CMS;
use Destructr\Search;

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

    public function invalidateCache(string $dso_id)
    {
        if ($cache = $this->cms->cache($this->cms->cache['cache.factorycache.adapter'])) {
            $cache->invalidateTags([$dso_id]);
        }
    }

    public function executeSearch(Search $search, array $params = array(), $deleted = false) : array
    {
        //add deletion clause and expand column names
        $search = $this->preprocessSearch($search, $deleted);
        //get cache
        $cache = $this->cms->cache($this->cms->config['cache.factorycache.adapter']);
        $id = md5(serialize([$search,$params,$deleted]));
        //check cache for results
        if ($cache && $cache->hasItem($id)) {
            //load result from cache
            $start = microtime(true);
            $r = $cache->getItem($id)->get();
            $duration = 1000*(microtime(true)-$start);
            $this->cms->log('factorycache hit loaded in '.$duration.'ms');
        } else {
            //run select
            $start = microtime(true);
            $r = $this->driver->select(
                $this->table,
                $search,
                $params
            );
            $duration = 1000*(microtime(true)-$start);
            $this->cms->log('query took '.$duration.'ms');
            if ($cache && $duration > $this->cms->config['cache.factorycache.threshold']) {
                $this->cms->log('saving results into factorycache');
                //build list of tags from dso_id
                $tags = [];
                foreach ($r as $i) {
                    $tags[] = $i['dso_id'];
                }
                //save to cache
                $citem = $cache->getItem($id);
                $citem->tag($tags);
                $citem->set($r);
                $cache->save($citem);
            }
        }
        //return built list
        return $this->makeObjectsFromRows($r);
    }
}
