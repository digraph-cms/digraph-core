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
        return array_map(
            function ($n) {
                return [
                    'id' => $n['dso.id'],
                    'name' => $n->name(),
                    'title' => $n->title(),
                    'url' => $n->url()->string()
                ];
            },
            $this->cms->helper('search')->search($q)
        );
    }

    public function handler_form_noun($q)
    {
        $result = [];
        foreach ($this->cms->helper('search')->search($q) as $r) {
            $result[$r['dso.id']] = $r->name();
        }
        return $result;
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
