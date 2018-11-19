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
        $text = preg_replace_callback(
            $this->regex(),
            function ($matches) {
                //figure out method name
                $method = 'tag_'.strtolower($matches[1]);
                if (!method_exists($this, $method)) {
                    return $matches[0];
                }
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
                //send to method
                $context = @$matches[3];
                if (!$context) {
                    $context = $this->context;
                }
                $out = $this->$method(
                    $context,//context
                    @$matches[8],//text
                    $args
                );
                return $out?$out:$matches[0];
            },
            $text
        );
        return $text;
    }

    protected function regex()
    {
        $regex = '';
        $regex .= '\[([a-z]+)';//open opening tag
        $regex .= '(:([^\] ]+))?';//context argument
        $regex .= '(( +[a-z0-9\-_]+(=.+?)?)*)';//named args
        $regex .= ' *\]';//close opening tag
        $regex .= '((.*?)';//content -- plus opening paren for making closing tag optional
        $regex .= '\[\/\1\])?';//closing tag
        return '/'.$regex.'/ims';
    }
}
