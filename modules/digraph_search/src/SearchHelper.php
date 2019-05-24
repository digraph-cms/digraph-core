<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_search;

use Digraph\DSO\Noun;
use Digraph\Helpers\AbstractHelper;
use TeamTNT\TNTSearch\TNTSearch;

class SearchHelper extends AbstractHelper
{
    protected $tnt;
    protected $indexer;
    protected $transaction = 0;

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
        $hooks->noun_register('update', [$this,'queueIndex'], 'search/index');
        $hooks->noun_register('insert', [$this,'queueIndex'], 'search/index');
        $hooks->noun_register('delete', [$this,'queueDelete'], 'search/delete');
        $hooks->noun_register('delete_permanent', [$this,'queueDelete'], 'search/delete');
        //hooks to index parents/children as well
        $hooks->noun_register('parent:update', [$this,'queueIndex'], 'search/index');
        $hooks->noun_register('child:update', [$this,'queueIndex'], 'search/index');
        $hooks->noun_register('parent:insert', [$this,'queueIndex'], 'search/index');
        $hooks->noun_register('child:insert', [$this,'queueIndex'], 'search/index');
        $hooks->noun_register('parent:delete', [$this,'queueIndex'], 'search/index');
        $hooks->noun_register('child:delete', [$this,'queueIndex'], 'search/index');
    }

    public function hook_cron()
    {
        $count = 0;
        $errors = [];
        $queue = $this->cms->helper('data')->facts('search_index_queue');
        if ($list = $queue->list()) {
            $this->beginTransaction();
            while ($list && $count < 10) {
                $fact = array_shift($list);
                if ($fact->data()['action'] == 'delete') {
                    $count++;
                    $this->delete($fact->about());
                } elseif ($noun = $this->cms->read($fact->about())) {
                    $count++;
                    $this->index($noun);
                } else {
                    $errors[] = 'couldn\'t index '.$fact->about();
                }
                $queue->delete($fact);
            }
            $this->endTransaction();
        }
        return [
            'result' => count($pruned),
            'errors' => $errors
        ];
    }

    public function queueIndex($noun)
    {
        $noun = $this->sanitizeNoun($noun);
        $this->cms->helper('data')->facts('search_index_queue')->create(
            'queue', //name
            time(), //value,
            $noun, //about
            ['action'=>'index'] //data
        );
    }

    public function queueDelete($noun)
    {
        $noun = $this->sanitizeNoun($noun);
        $this->cms->helper('data')->facts('search_index_queue')->create(
            'queue', //name
            time(), //value,
            $noun, //about
            ['action'=>'delete'] //data
        );
    }

    protected function sanitizeNoun($noun)
    {
        if (!$noun) {
            return '';
        }
        if ($noun instanceof Noun) {
            return $noun['dso.id'];
        }
        $noun = strtolower($noun);
        $noun = preg_replace('/[^a-z0-9]/', '', $noun);
        return $noun;
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
        $result = $this->tnt->search($query)['ids'];
        //direct-read
        foreach ($this->cms->locate(trim($query)) as $d) {
            $result[] = $d['dso.id'];
            $result = array_unique($result);
        }
        //set up highlights
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
            $result
        );
        //filter and sort before returning
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
        $slug = strtolower($noun->url()['noun']);
        $id = strtolower($noun['dso.id']);
        $score = 0;
        if ($name == $query) {
            $score += 100;
        }
        if ($title == $query) {
            $score += 100;
        }
        if ($slug == $query) {
            $score += 100;
        }
        if ($id == $query) {
            $score += 100;
        }
        $posName = strpos($name, $query);
        $posTitle = strpos($title, $query);
        $posSlug = strpos($slug, $query);
        $posId = strpos($id, $query);
        if ($posSlug === 0) {
            $score += 50;
        } elseif ($posSlug !== false) {
            $score += 20;
        }
        if ($posId === 0) {
            $score += 50;
        } elseif ($posId !== false) {
            $score += 20;
        }
        if ($posName === 0) {
            $score += 50;
        } elseif ($posName !== false) {
            $score += 20;
        }
        if ($posTitle === 0) {
            $score += 50;
        } elseif ($posTitle !== false) {
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

    protected function &indexer()
    {
        if (!$this->indexer) {
            try {
                $this->tnt->selectIndex('digraph.index');
            } catch (\Exception $e) {
                $this->tnt->createIndex('digraph.index');
                $this->tnt->selectIndex('digraph.index');
            }
            $this->indexer = $this->tnt->getIndex();
        }
        return $this->indexer;
    }

    public function delete($noun)
    {
        $noun = $this->sanitizeNoun($noun);
        $this->beginTransaction();
        $this->indexer()->delete($noun);
        $this->endTransaction();
    }

    public function beginTransaction()
    {
        if ($this->transaction == 0) {
            $this->indexer()->indexBeginTransaction();
            $this->transaction++;
        }
    }

    public function endTransaction()
    {
        if ($this->transaction > 0) {
            $this->indexer()->indexEndTransaction();
            $this->transaction--;
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
        $this->beginTransaction();
        $this->indexer()->update(
            $noun['dso.id'],
            $data
        );
        $this->endTransaction();
    }
}
