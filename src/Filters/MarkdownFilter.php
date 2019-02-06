<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

/**
 * Text filter converts multiple newlines into paragraph breaks. It should
 * usually either be preceded by SanitizeFilter or followed by HTMLFilter with
 * the P tag allowed.
 */
class MarkdownFilter extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        $text = str_replace('\\[', '\\\\\\[', $text);
        $text = str_replace('\\]', '\\\\\\]', $text);
        return \Parsedown::instance()
            ->setMarkupEscaped(true)
            ->setUrlsLinked(false)
            ->text($text);
    }
}
