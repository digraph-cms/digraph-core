<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

class MarkdownFilterUnsafe extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        $text = str_replace('\\[', '\\\\\\[', $text);
        $text = str_replace('\\]', '\\\\\\]', $text);
        return \ParsedownExtra::instance()
            ->setMarkupEscaped(false)
            ->setUrlsLinked(false)
            ->text($text);
    }
}
