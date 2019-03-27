<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_api;

use Digraph\Helpers\AbstractHelper;

class APIHelper extends AbstractHelper
{
    protected $handlers = [];

    public function initialize()
    {
        //register field types with form helper
        $f = $this->cms->helper('forms');
        $f->registerType('noun', Fields\AjaxNounField::class);
    }

    public function construct()
    {
        $this->register('noun', [$this,'handler_noun']);
        $this->register('form_noun', [$this,'handler_form_noun']);
    }

    public function handler_noun($q)
    {
        if (!$q || $q === true) {
            return [];
        }
        $q = "%$q%";
        $search = $this->cms->factory()->search();
        $search->where('${digraph.name} like :q OR ${digraph.title} LIKE :q OR ${dso.id} LIKE :q');
        $search->order('${dso.modified} desc');
        $result = array_map(
            function ($n) {
                return [
                    'id' => $n['dso.id'],
                    'name' => $n->name(),
                    'title' => $n->title(),
                    'url' => $n->url()->string()
                ];
            },
            $search->execute([':q'=>$q])
        );
        $result = $this->orderByRelevancy($result, $q);
        return $result;
    }

    public function handler_form_noun($q)
    {
        $result = [];
        foreach ($this->call('noun', $q) as $r) {
            $result[$r['id']] = $r['name'];
        }
        return $result;
    }

    protected function orderByRelevancy($result, $q)
    {
        $sorted = [];
        $q = strtolower($q);
        $q = preg_replace('/[^a-z0-9]+/', ' ', $q);
        $q = preg_split('/\s+/', $q);
        foreach ($result as $k => $v) {
            $v = serialize($v);
            $v = strtolower($v);
            $v = preg_replace('/[^a-z0-9]+/', ' ', $v);
            $v = preg_split('/\s+/', $v);
            $score = 0;
            foreach ($q as $qi) {
                foreach ($v as $vi) {
                    if ($qi && $vi) {
                        if ($qi == $vi) {
                            $score += strlen($qi)*2;
                        } elseif (strpos($vi, $qi) !== false) {
                            $score += strlen($qi);
                        }
                    }
                }
            }
            $sorted[str_pad($score, 32, '0', STR_PAD_LEFT).'-'.md5(serialize($v))] = $result[$k];
        }
        krsort($sorted);
        return array_values($sorted);
    }

    public function register($name, $callable)
    {
        $this->handlers[$name] = $callable;
    }

    public function call($name, $q)
    {
        if (!isset($this->handlers[$name])) {
            return null;
        }
        if (!is_array($q)) {
            $q = [$q];
        }
        return call_user_func_array($this->handlers[$name], $q);
    }
}
