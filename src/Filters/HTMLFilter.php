<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

class HTMLFilter extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        $allowed = '';
        foreach ($this->cms->config['filters.htmlfilter.allowed'] as $tag) {
            $allowed .= "<$tag>";
        }
        return strip_tags($text, $allowed);
    }
}
