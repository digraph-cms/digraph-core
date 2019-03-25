<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\CMS;

class HookHelper extends \Digraph\Helpers\AbstractHelper
{
    protected $hooks = [];

    public function register(string $target, string $event, $callable, $name=null)
    {
        if (!$name) {
            $name = uniqid();
        }
        @$this->hooks[$target][$event][$name] = $callable;
        $this->cms->log('registering hook: '.(implode(', ', [$target,$event,$name])));
        return $name;
    }

    public function trigger(string $target, string $event, $args = [])
    {
        if (@$this->hooks[$target][$event]) {
            foreach ($this->hooks[$target][$event] as $value) {
                call_user_func_array($value, $args);
            }
        }
    }

    public function noun_register(string $event, $callable, $name=null)
    {
        return $this->register('nouns', $event, $callable, $name);
    }

    public function noun_trigger($noun, string $event)
    {
        $this->trigger('nouns', $event, [$noun]);
        //recurse into parents, triggering child:$event events
        $this->noun_recurse_up($noun->parents(), 'child:'.$event, [$noun]);
        //recurse into children, triggering parent:$event events
        $this->noun_recurse_down($noun->children(), 'parent:'.$event, [$noun]);
    }

    protected function noun_recurse_up($nouns, $event, $seen)
    {
        foreach ($nouns as $noun) {
            if (!in_array($noun['dso.id'], $seen)) {
                $this->trigger('nouns', $event, [$noun]);
                $seen[] = $noun['dso.id'];
                $this->noun_recurse_up($noun->parents(), $event, $seen);
            }
        }
    }

    protected function noun_recurse_down($nouns, $event, $seen)
    {
        foreach ($nouns as $noun) {
            if (!in_array($noun['dso.id'], $seen)) {
                $this->trigger('nouns', $event, [$noun]);
                $seen[] = $noun['dso.id'];
                $this->noun_recurse_down($noun->children(), $event, $seen);
            }
        }
    }
}
