<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;

class Actions extends AbstractHelper
{
    public function addable($search)
    {
        //make a list of all types
        $types = [];
        foreach ($this->cms->config['types.content'] as $type => $class) {
            if ($type == 'default') {
                continue;
            }
            $types[] = $type;
        }
        //filter with permissions helper
        $p = $this->cms->helper('permissions');
        $types = array_filter(
            $types,
            function ($e) use ($p,$search) {
                return $p->checkAddPermissions($search, $e);
            }
        );
        //return results
        asort($types);
        return array_values($types);
    }

    public function get($noun)
    {
        $links = [];
        $vars = [
            'noun' => $noun
        ];
        if ($object = $this->cms->read($noun)) {
            $proper = true;
            $rules = $this->cms->config['actions.proper'];
            $vars['type'] = $noun = $object['dso.type'];
            $vars['id'] = $object['dso.id'];
        } else {
            $proper = false;
            $rules = $this->cms->config['actions.common'];
        }
        //extract matching rules
        $links = $rules['*'];
        if (isset($rules[$noun])) {
            $links = array_replace_recursive($links, $rules[$noun]);
        }
        //allow noun to mess with links if it wants to
        if ($object && method_exists($object, 'actions')) {
            $links = $object->actions($links);
        }
        //apply variables to links
        $links = array_map(
            function ($e) use ($vars) {
                foreach ($vars as $key => $value) {
                    $e = str_replace("!$key", $value, $e);
                }
                return $e;
            },
            $links
        );
        //filter with permissions
        $links = array_filter(
            $links,
            function ($e) {
                if (!($url = $this->cms->helper('urls')->parse($e))) {
                    return false;
                }
                return $this->cms->helper('permissions')->checkUrl($url);
            }
        );
        //return links
        asort($links);
        return array_values($links);
    }
}
