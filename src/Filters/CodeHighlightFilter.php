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
                //do highlighting
                $lang = @$matches[2];
                $code = trim($matches[3]);
                $code = preg_replace("/&amp;(.{1,5});/", '&$1;', $code);
                try {
                    $highlighted = $h->highlight($lang, $code);
                } catch (\Exception $e) {
                    $highlighted = $h->highlightAuto($code);
                }
                $code = $highlighted->value;
                //fix double-coding
                $code = preg_replace("/&amp;(.{1,5});/", '&$1;', $code);
                //escape bbcode
                $code = str_replace('[', '\\[', $code);
                $code = str_replace(']', '\\]', $code);
                //wrap lines if there are multiple lines
                if (preg_match('/[\r\n]/', $code)) {
                    $code = preg_split('/(\r\n|\n|\r)/', $code);
                    $code = array_map(
                        function ($e) {
                            return '<span class="code-highlighted-line">'.$e.'</span>';
                        },
                        $code
                    );
                    $code = implode(PHP_EOL, $code);
                    //return as a DIV
                    return '<div class="code-highlighted language-'.$highlighted->language.'">'.$code.'</div>';
                }
                //return as a SPAN
                return '<span class="code-highlighted language-'.$highlighted->language.'">'.$code.'</span>';
            },
            $text
        );
        return $text;
    }
}
