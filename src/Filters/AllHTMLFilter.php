<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

class AllHTMLFilter extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        return strip_tags($text);
    }
}
