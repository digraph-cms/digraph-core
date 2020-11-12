<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\CMS;

class HookHelper extends \Digraph\Helpers\AbstractHelper
{
    protected $hooks = [];

    public function register(string $target, string $event, $callable, $name = null)
    {
        if (!$name) {
            $name = uniqid();
        }
        @$this->hooks[$target][$event][$name] = $callable;
        $this->cms->log('registering hook: ' . (implode(', ', [$target, $event, $name])));
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

    public function noun_register(string $event, $callable, $name = null)
    {
        return $this->register('nouns', $event, $callable, $name);
    }

    public function noun_trigger($noun, string $event)
    {
        $this->trigger('nouns', $event, [$noun]);
        //recurse into parents, triggering child:$event events
        if ($noun::HOOK_TRIGGER_PARENTS) {
            $this->noun_recurse_up($noun, 'child:' . $event);
        }
        //recurse into children, triggering parent:$event events
        if ($noun::HOOK_TRIGGER_CHILDREN) {
            $this->noun_recurse_down($noun, 'parent:' . $event);
        }
    }

    protected function noun_recurse_up($noun, $event)
    {
        $this->cms->helper('graph')->traverse(
            $noun['dso.id'],
            function ($noun) use ($event) {
                $this->trigger('nouns', $event, [$noun]);
            },
            null,
            -1,
            true
        );
    }

    protected function noun_recurse_down($noun, $event)
    {
        $this->cms->helper('graph')->traverse(
            $noun['dso.id'],
            function ($noun) use ($event) {
                $this->trigger('nouns', $event, [$noun]);
            },
            null,
            -1,
            false
        );
    }
}
