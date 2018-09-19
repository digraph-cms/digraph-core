<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;

class Actions extends AbstractHelper
{
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
        return $links;
    }
}
