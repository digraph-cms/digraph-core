<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_search;

use Digraph\Helpers\AbstractHelper;
use TeamTNT\TNTSearch\TNTSearch;

class SearchHelper extends AbstractHelper
{
    protected $tnt;
    protected $indexer;

    public function initialize()
    {
        //set up search object
        $this->tnt = new TNTSearch;
        $this->tnt->loadConfig([
            'driver' => 'filesystem',
            'storage' => $this->cms->config['paths.storage']
        ]);
        //set up hooks to index nouns on insert/update/delete
        $hooks = $this->cms->helper('hooks');
        $hooks->noun_register('update', [$this,'index'], 'search/index');
        $hooks->noun_register('insert', [$this,'index'], 'search/index');
        $hooks->noun_register('delete', [$this,'delete'], 'search/delete');
        $hooks->noun_register('delete_permanent', [$this,'delete'], 'search/delete');
    }

    protected function indexer()
    {
        if (!$this->indexer) {
            try {
                $this->tnt->selectIndex('digraph.index');
                $this->indexer = $this->tnt->getIndex();
            } catch (\Exception $e) {
                $this->indexer = $this->tnt->createIndex('digraph.index');
            }
            $this->tnt->selectIndex('digraph.index');
            $this->indexer->includePrimaryKey();
        }
        return $this->indexer;
    }

    public function search($query)
    {
        $this->indexer();
        $result = $this->tnt->search($query);
        $result = array_map(
            function ($e) {
                return $this->cms->read($e);
            },
            $result['ids']
        );
        $result = array_filter($result);
        return $result;
    }

    public function shouldBeIndexed($noun)
    {
        if (method_exists($noun, 'searchIndexed')) {
            return $noun->searchIndexed();
        }
        return true;
    }

    public function delete($noun)
    {
        $idx = $this->indexer();
        $idx->indexBeginTransaction();
        $idx->delete($noun['dso.id']);
        $idx->indexEndTransaction();
    }

    public function index($noun)
    {
        //verify that this noun should be indexed, remove it if it's deleted or
        //should not be indexed
        if ($noun['dso.deleted'] || !$this->shouldBeIndexed($noun)) {
            $this->delete($noun);
            return;
        }
        //insert/update index entry
        $data = [
            'id' => $noun['dso.id'],
            'title' => $noun->name(),
            'article' => $noun->body()
        ];
        $idx = $this->indexer();
        $idx->indexBeginTransaction();
        $idx->update(
            $noun['dso.id'],
            $data
        );
        $idx->indexEndTransaction();
    }
}
