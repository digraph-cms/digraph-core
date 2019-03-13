<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\Factory;
use Digraph\CMS;
use Destructr\Search;
use Destructr\DSOInterface;
use Flatrr\FlatArray;

class DigraphFactory extends Factory
{
    const ID_LENGTH = 16;
    protected $cms;
    protected $name = 'system';

    public function name($set = null) : string
    {
        if ($set) {
            $this->name = $set;
        }
        return $this->name;
    }

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

    protected function hook_create(DSOInterface &$dso)
    {
        parent::hook_create($dso);
        if (!isset($dso['dso.created.user.id'])) {
            if ($id = $this->cms->helper('users')->id()) {
                $dso['dso.created.user.id'] = $id;
            } else {
                $dso['dso.created.user.id'] = 'guest';
            }
        }
    }

    protected function hook_update(DSOInterface &$dso)
    {
        parent::hook_update($dso);
        if ($id = $this->cms->helper('users')->id()) {
            $dso['dso.modified.user.id'] = $id;
        } else {
            $dso['dso.modified.user.id'] = 'guest';
        }
    }


    public function class(array $data) : ?string
    {
        $data = new FlatArray($data);
        $type = $data['dso.type'];
        if (!$type || !isset($this->cms->config['types.'.$this->name.'.'.$type])) {
            $type = 'default';
        }
        if ($class = $this->cms->config['types.'.$this->name.'.'.$type]) {
            return $class;
        }
        throw new \Exception("No class could be found for factory ".$this->name.", type ".$data['dso']['type'], 1);
    }

    public function &cms(CMS &$set=null) : CMS
    {
        if ($set) {
            $this->cms = $set;
        }
        return $this->cms;
    }

    public function executeSearch(Search $search, array $params = array(), $deleted = false) : array
    {
        //add deletion clause and expand column names
        $search = $this->preprocessSearch($search, $deleted);
        //run select
        $start = microtime(true);
        $r = $this->driver->select(
                $this->table,
                $search,
                $params
            );
        $duration = 1000*(microtime(true)-$start);
        $this->cms->log('query took '.$duration.'ms');
        $this->cms->log('  '.$search->where());
        foreach ($params as $key => $value) {
            $this->cms->log('  '.$key.' = '.$value);
        }
        //return built list
        return $this->makeObjectsFromRows($r);
    }
}
