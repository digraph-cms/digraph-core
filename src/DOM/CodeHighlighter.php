<?php

namespace DigraphCMS\DOM;

use DigraphCMS\UI\Theme;
use DomainException;
use DOMElement;
use Highlight\Highlighter;

class CodeHighlighter
{
    /**
     * Handle <code> tags in DOM
     */
    public static function codeEvent(DOMEvent $event)
    {
        // get node and classes
        /** @var DOMElement */
        $node = $event->getNode();
        $classes = array_filter(explode(' ', $node->getAttribute('class') ?? ''));
        // abort if class nohighlight is found
        if (in_array('nohighlight', $classes)) {
            return;
        }
        // abort if parent node is not a PRE
        // if ($node->parentNode instanceof DOMElement) {
        //     if ($node->parentNode->tagName != 'pre') {
        //         return;
        //     }
        // }
        // try to find a language specified in the classes
        $lang = null;
        if (in_array('plaintext', $classes)) {
            $lang = 'plaintext';
        } else {
            foreach ($classes as $class) {
                if (preg_match('/^lang?-(.+)$/', $class, $matches)) {
                    $lang = $matches[1];
                } elseif (preg_match('/^language?-(.+)$/', $class, $matches)) {
                    $lang = $matches[1];
                }
            }
        }
        // do highlighting
        $result = static::highlight($node->textContent, $lang);
        $classes[] = 'hljs';
        $classes[] = 'lang-' . $result->language;
        $classes[] = 'language-' . $result->language;
        $classes = array_unique($classes);
        // replace node with this updated one
        $event->setReplacement("<code class=\"" . implode(' ', $classes) . "\">" . $result->value . "</code>");
    }

    public static function autodetectable(): array
    {
        return [
            'css',
            'http',
            'javascript',
            'json',
            'markdown',
            'php',
            'scss',
            'sql',
            'twig',
            'twig',
            'xml',
            'yaml',
        ];
    }

    /**
     * Highlight the given code, autodetecting if no language is provided,
     * returns a result from Highlight.php, which has the attributes ->value
     * and ->language that can be used to access the highlighted code and
     * what language was autodetected if none was specified.
     *
     * @param string $code
     * @param string $lang
     * @return object
     */
    public static function highlight(string $code, string $lang = null): object
    {
        static::loadCSS();
        $hl = new Highlighter();
        try {
            if ($lang) {
                $result = $hl->highlight($lang, $code);
            } else {
                $hl->setAutodetectLanguages(static::autodetectable());
                $result = $hl->highlightAuto($code);
            }
        } catch (DomainException $th) {
            //thrown if the specified language doesn't exist
            $hl->setAutodetectLanguages(static::autodetectable());
            $result = $hl->highlightAuto($code);
        }
        return $result;
    }

    public static function loadCSS()
    {
        static $loaded = false;
        if (!$loaded) {
            Theme::addInternalPageCss('/hljs/*.css');
            $loaded = true;
        }
    }
}
