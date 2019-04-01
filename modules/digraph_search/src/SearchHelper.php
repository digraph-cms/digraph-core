<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_search;

use Digraph\Helpers\AbstractHelper;
use TeamTNT\TNTSearch\TNTSearch;

class SearchHelper extends AbstractHelper
{
    protected $tnt;
    protected $indexer;
    protected $transaction;

    public function initialize()
    {
        //set up search object
        $this->tnt = new TNTSearch;
        $this->tnt->loadConfig([
            'driver' => 'filesystem',
            'storage' => $this->cms->config['paths.storage']
        ]);
        $this->tnt->fuzziness = true;
        //set up hooks to index nouns on insert/update/delete
        $hooks = $this->cms->helper('hooks');
        $hooks->noun_register('update', [$this,'index'], 'search/index');
        $hooks->noun_register('child:update', [$this,'index'], 'search/index');
        $hooks->noun_register('insert', [$this,'index'], 'search/index');
        $hooks->noun_register('child:insert', [$this,'index'], 'search/index');
        $hooks->noun_register('delete', [$this,'delete'], 'search/delete');
        $hooks->noun_register('child:delete', [$this,'index'], 'search/index');
        $hooks->noun_register('delete_permanent', [$this,'delete'], 'search/delete');
    }

    public function hook_cron()
    {
        $count = 0;
        $interval = $this->cms->config['searh.cron.interval'];
        $limit = $this->cms->config['search.cron.limit'];
        $search = $this->cms->factory()->search();
        $search->where('${digraph.lastsearchindex} is null OR ${digraph.lastsearchindex} < :time');
        $search->limit($limit);
        $this->beginTransaction();
        foreach ($search->execute([':time'=>(time()-$interval)]) as $noun) {
            $this->index($noun);
            $noun['digraph.lastsearchindex'] = time();
            $noun->update(true);
            $count++;
        }
        $this->endTransaction();
        return $count;
    }

    public function form()
    {
        $form = new \Formward\Fields\Container('', 'search');
        $form->tag = 'form';
        $form->addClass('Form');
        $form->addClass('search-form');
        $form->attr('action', $this->cms->helper('urls')->url('_search', 'display'));
        $form->method('get');
        $form['q'] = new \Formward\Fields\Input('');
        $form['submit'] = new \Formward\SystemFields\Submit('Search');
        return $form;
    }

    public function search($query)
    {
        $this->indexer();
        $result = $this->tnt->search($query);
        $result = array_map(
            function ($e) use ($query) {
                if ($noun = $this->cms->read($e)) {
                    return [
                        'noun' => $noun,
                        'highlights' => $this->highlights($query, $noun)
                    ];
                }
                return false;
            },
            $result['ids']
        );
        $result = array_filter($result);
        return $result;
    }

    public function highlights($query, $noun)
    {
        return [
            'The following is being produced after normal execution by the digraph_debug_module. This module should never be used in production.',
            'ModuleManager: loading C:\xampp\htdocs\digraph-core\example/modules/example_module/module.yaml'
        ];
    }

    public function shouldBeIndexed($noun)
    {
        if (method_exists($noun, 'searchIndexed')) {
            return $noun->searchIndexed();
        }
        return true;
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
            $this->tnt->getIndex()->includePrimaryKey();
        }
        return $this->indexer;
    }

    public function delete($noun)
    {
        $idx = $this->indexer();
        $idx->indexBeginTransaction();
        $idx->delete($noun['dso.id']);
        $idx->indexEndTransaction();
    }

    public function beginTransaction()
    {
        if (!$this->transaction) {
            $this->indexer()->indexBeginTransaction();
            $this->transaction = true;
        }
    }

    public function endTransaction()
    {
        if ($this->transaction) {
            $this->indexer()->indexEndTransaction();
            $this->transaction = false;
        }
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
            'article' => $noun->name().' '.$noun->title().' '.$noun->body()
        ];
        $idx = $this->indexer();
        if (!$this->transaction) {
            $idx->indexBeginTransaction();
        }
        $idx->update(
            $noun['dso.id'],
            $data
        );
        if (!$this->transaction) {
            $idx->indexEndTransaction();
        }
    }
}
