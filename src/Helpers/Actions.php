<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;

class Actions extends AbstractHelper
{
    public function addable($search)
    {
        $types = [];
        $rules = $this->cms->config['actions.addable'];
        // loop through rules, the first key is a type that can be added,
        // and the following keys are the types the first key can be added under
        foreach ($rules as $type => $typeRules) {
            $allowed = false;
            foreach ($typeRules as $rule => $value) {
                if ($rule == '*' || $rule == $search) {
                    $allowed = $value;
                }
            }
            if ($allowed) {
                $types[] = $type;
            }
        }
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
            $noun = $package['url.noun'];
        }
        //extract matching rules
        $links = $rules['*'];
        if (isset($rules[$noun])) {
            $links = array_replace_recursive($links, $rules[$noun]);
        }
        //allow noun to mess with links if it wants to
        if (method_exists($object, 'actions')) {
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
