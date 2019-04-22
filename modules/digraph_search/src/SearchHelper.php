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
        $this->sort($query, $result);
        return $result;
    }

    protected function sortScore($noun, $query)
    {
        $query = strtolower($query);
        $name = strtolower($noun->name());
        $title = strtolower($noun->title());
        $body = strtolower($noun->body());
        $score = 0;
        if ($name == $query) {
            $score += 100;
        }
        if ($title == $query) {
            $score += 100;
        }
        if (strpos($name, $query) !== false) {
            $score += 20;
        }
        if (strpos($title, $query) !== false) {
            $score += 20;
        }
        if (strpos($body, $query) !== false) {
            $score += 10;
        }
        return $score;
    }

    protected function sort($query, &$result)
    {
        uasort($result, function ($a, $b) use ($query) {
            $as = $this->sortScore($a['noun'], $query);
            $bs = $this->sortScore($b['noun'], $query);
            if ($as == $bs) {
                return 0;
            } elseif ($as > $bs) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    public function highlights($query, $noun)
    {
        $text = \Soundasleep\Html2Text::convert(
            $noun->body(),
            [
                'ignore_errors' => true,
                'drop_links' => true
            ]
        );
        $text = $this->tnt->highlight($text, $query);
        $length = $this->cms->config['search.highlight.length'];
        $positions = [];
        while (($lastPos = strpos($text, '<em>', @$lastPos))!== false) {
            $positions[] = $lastPos;
            $lastPos = $lastPos + strlen('<em>');
        }
        $highlights = [];
        $limit = -1;
        foreach ($positions as $pos) {
            if ($pos <= $limit) {
                continue;
            }
            $hl = substr($text, $pos, $length);
            $limit = $pos + $length;
            $highlights[substr_count($hl, '<em>')-$pos] = $this->tnt->highlight(strip_tags($hl), $query);
        }
        krsort($highlights);
        return array_slice($highlights, 0, $this->cms->config['search.highlight.count']);
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
            'article' => implode(' ', [
                $noun->url()['noun'],
                $noun->url()['canonicalnoun'],
                $noun->name(),
                $noun->title(),
                $noun->body()
            ])
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
