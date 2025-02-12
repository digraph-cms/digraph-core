<?php

namespace DigraphCMS\SafeContent;

use DigraphCMS\UI\Format;
use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMNode;
use DOMText;
use Masterminds\HTML5;

/**
 * Recurses through the DOM of a given HTML fragment after BBCode is parsed and
 * processes any additional steps, such as finding URLs and email addresses and
 * converting them into links.
 */
class SafeBBCodeHtmlParser
{
    public static function parse(string $html): string
    {
        $html5 = new HTML5();
        $dom = $html5->loadHTMLFragment($html);
        static::walk($dom);
        return $html5->saveHTML($dom);
    }

    protected static function walk(DOMNode $node)
    {
        // process elements
        if ($node instanceof DOMElement) {
            // process and then don't recurse into links
            if ($node->tagName == 'a') {
                return;
            }
            // recurse into other elements
            foreach ($node->childNodes as $child) {
                static::walk($child);
            }
        }
        // process text nodes
        if ($node instanceof DOMText) {
            $text = $node->textContent;
            // turn URLs into links
            $text = preg_replace_callback('/\bhttps?:\/\/\S+\b/', function ($matches) {
                if (!filter_var($matches[0], FILTER_VALIDATE_URL)) {
                    return $matches[0];
                }
                return "<a href='{$matches[0]}' target='_blank'>{$matches[0]}</a>";
            }, $text);
            // turn email addresses into obfuscated links
            $text = preg_replace_callback('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/', function ($matches) {
                if (!filter_var($matches[0], FILTER_VALIDATE_EMAIL)) {
                    return $matches[0];
                }
                return Format::base64obfuscate("<a href=\"mailto:{$matches[0]}\">{$matches[0]}</a>");
            }, $text);
            // replace text node with text converted into multiple parsed dom nodes
            $fragment = $node->ownerDocument->createDocumentFragment();
            $fragment->appendXML($text);
            $node->parentNode->replaceChild($fragment, $node);
        }
        // recurse into fragments
        if ($node instanceof DOMDocumentFragment) {
            foreach ($node->childNodes as $child) {
                static::walk($child);
            }
        }
    }
}
