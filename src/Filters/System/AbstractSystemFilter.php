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
            function ($matches) use ($opts) {
                //figure out method name
                $tag = strtolower($matches[1]);
                $method = 'tag_'.$tag;
                if (!method_exists($this, $method)) {
                    if (method_exists($this, 'tag')) {
                        $method = 'tag';
                    } else {
                        return $matches[0];
                    }
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
                if ($text = @$matches[9]) {
                    $text = $this->filter($text, $opts);
                }
                if (!$context) {
                    $context = $this->context;
                }
                if ($method == 'tag') {
                    //pass tag to generic 'tag' handler method
                    $out = $this->$method(
                        $tag,//tag
                        $context,//context
                        $text,//text
                        $args
                    );
                } else {
                    //named method
                    $out = $this->$method(
                        $context,//context
                        $text,//text
                        $args
                    );
                }
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
        $regex .= ' *(\/\]|\]';//self-close opening tag, or not self-closed so we might have text
        $regex .= '((.*?)';//content -- plus opening paren for making closing tag optional
        $regex .= '\[\/\1\])?)';//closing tag
        return '/'.$regex.'/ims';
    }
}
