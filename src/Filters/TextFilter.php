<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

/**
 * Text filter converts multiple newlines into paragraph breaks. It should
 * usually either be preceded by SanitizeFilter or followed by HTMLFilter with
 * the P tag allowed.
 */
class TextFilter extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        $text = preg_split('/(\r?\n){2,}/', $text);
        return '<p>'.implode('</p>'.PHP_EOL.'<p>', $text).'</p>';
    }
}
