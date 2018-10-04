<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

class SanitizeFilter extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        return htmlspecialchars($text);
    }
}
