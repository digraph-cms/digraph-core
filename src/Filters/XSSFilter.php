<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

class XSSFilter extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        $filter = new \lincanbin\WhiteHTMLFilter();
        $filter->loadHTML($text);
        $filter->clean();
        return $filter->outputHTML();
    }
}
