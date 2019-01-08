<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;

class Actions extends AbstractHelper
{
    /**
     * Retrieve an array of all the types that can be added under a given noun.
     * The output of this field is actually controlled by the permissions helper
     * so check out PermissionsHelper::checkAddPermissions for more about how
     * to configure addable lists.
     */
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

    /**
     * Pull a list of rules for the given noun from another named list of
     * actions in config.
     *
     * Proper nouns always get their action list passed through the object, so
     * objects get to add/remove actions from their own lists.
     */
    public function other($noun, $type='categorical')
    {
        //make sure rules exist
        if (!($rules = $this->cms->config['actions.'.$type])) {
            return [];
        }
        //return results
        return $this->results($noun, $rules, $vars);
    }

    /**
     * The most basic implementation, designed for use in actionbars. For proper
     * nouns it pulls from actions.proper config, and for common nouns it pulls
     * from actions.common
     *
     * Proper nouns always get their action list passed through the object, so
     * objects get to add/remove actions from their own lists.
     */
    public function get($noun)
    {
        if ($object = $this->cms->read($noun)) {
            $rules = $this->cms->config['actions.proper'];
        } else {
            $rules = $this->cms->config['actions.common'];
        }
        //return results
        return $this->results($noun, $rules);
    }

    /**
     * Given a noun and array of rules and additional variables, construct a
     * list of available (and allowed for the current user) actions for the
     * given noun. If the noun is a valid noun ID !type and !id variables are
     * automatically pulled from the object.
     */
    protected function results($noun, $rules, $vars = [])
    {
        $vars['noun'] = $noun;
        //check for object
        if ($object = $this->cms->read($noun)) {
            $vars['type'] = $object['dso.type'];
            $vars['id'] = $object['dso.id'];
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
