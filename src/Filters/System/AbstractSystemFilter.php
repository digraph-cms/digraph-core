<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters\System;

use Digraph\Filters\AbstractFilter;

/**
 * This abstract filter locates and processes Digraph system tags, and is
 * meant to be extended to build all the system tag filters.
 */
abstract class AbstractSystemFilter extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        foreach (preg_grep('/^tag_/', get_class_methods($this)) as $method) {
            $tag = substr($method, 4);
            $text = preg_replace_callback(
                $this->regex($tag),
                function ($matches) use ($method) {
                    // var_dump($matches);
                    //parse args
                    $args = [];
                    preg_match_all(
                        '/ +([^\= ]+)(=([\'"]|&quot;)(.+?)\3)?/',
                        $matches[4],
                        $argMatches
                    );
                    foreach ($argMatches[1] as $i => $name) {
                        $args[$name] = $argMatches[4][$i]?$argMatches[4][$i]:true;
                    }
                    // var_dump($args);
                    //send to method
                    $out = $this->$method(
                        @$matches[3],//primary
                        @$matches[8],//text
                        $args
                    );
                    return $out?$out:$matches[0];
                },
                $text
            );
        }
        return $text;
    }

    protected function regex($tag, $multiline=false)
    {
        $regex = '';
        $regex .= '\[('.$tag.')';//open opening tag
        $regex .= '(:([^\] ]+))?';//primary argument
        $regex .= '(( [a-z0-9\-_]+(=.+?)?)*)';//named args
        $regex .= ' *\]';//close opening tag
        $regex .= '((.*?)';//content
        $regex .= '\[\/'.$tag.'\])?';//closing tag
        return '/'.$regex.'/i'.($multiline?'ms':'');
    }
}
