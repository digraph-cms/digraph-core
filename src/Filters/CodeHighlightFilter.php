<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

use Highlight\Highlighter;

class CodeHighlightFilter extends AbstractFilter
{
    public function filter(string $text, array $opts = []) : string
    {
        $h = new Highlighter;
        $text = preg_replace_callback(
            "/<code( class=\"language-(.+?)\")?>(.+?)<\/code>/ims",
            function ($matches) use ($h) {
                $lang = @$matches[2];
                $code = trim($matches[3]);
                $code = preg_replace("/&amp;(.{1,5});/", '&$1;', $code);
                try {
                    $highlighted = $h->highlight($lang, $code);
                } catch (\Exception $e) {
                    $highlighted = $h->highlightAuto($code);
                }
                $code = $highlighted->value;
                $code = preg_replace("/&amp;(.{1,5});/", '&$1;', $code);
                return '<code class="code-highlighted language-'.$highlighted->language.'">'.$code.'</code>';
            },
            $text
        );
        return $text;
        // $dom = new Crawler($text);
        // $h = new Highlighter;
        // $codes = $dom->find('code');
        // foreach ($codes as $code) {
        //     $lang = null;
        //     $classes = preg_split('/ +/', trim($code->getAttribute('class')));
        //     foreach ($classes as $i => $class) {
        //         if (preg_match('/^language-/', $class)) {
        //             $lang = substr($class, 9);
        //             unset($classes[$i]);
        //         }
        //     }
        //     try {
        //         $highlighted = $h->highlight($lang, $code->innerHTML());
        //     } catch (\Exception $e) {
        //         $highlighted = $h->highlightAuto($code->innerHTML());
        //     }
        //     if ($highlighted) {
        //         $classes[] = 'highlighted';
        //         $classes[] = 'language-'.$highlighted->language;
        //         $code->setAttribute('class', implode(' ', $classes));
        //         $code->setText($highlighted->value);
        //     }
        // }
        // return "$dom";
    }
}
