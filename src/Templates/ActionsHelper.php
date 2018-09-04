<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Helpers\AbstractHelper;

class ActionsHelper extends AbstractHelper
{
    protected $actions = [];

    public function actions($type)
    {
        if (isset($this->actions[$type][$key])) {
            return $this->actions[$type];
        }
        return [];
    }

    public function addAction($type, $key, $url)
    {
        @$this->actions[$type][$key] = $url;
    }

    public function removeAction($type, $key)
    {
        if (isset($this->actions[$type][$key])) {
            unset($this->actions[$type][$key]);
        }
    }

    public function clearActions($type)
    {
        $this->actions[$type] = [];
    }
}
