<?php

namespace DigraphCMS\SafeContent;

use DigraphCMS\UI\Format;
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
        // process text nodes
        if ($node instanceof DOMText) {
            if (empty(trim($node->textContent))) {
                return;
            }
            $fragment = $node->ownerDocument->createDocumentFragment();
            $text = $node->textContent;
            $text = preg_replace_callback(
                '/\bhttps?:\/\/[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i',
                function ($matches) {
                    if (!filter_var($matches[0], FILTER_VALIDATE_URL)) {
                        return $matches[0];
                    }
                    return "<a href='{$matches[0]}' target='_blank'>{$matches[0]}</a>";
                },
                $text
            );
            $text = preg_replace_callback(
                '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/i',
                function ($matches) {
                    if (!filter_var($matches[0], FILTER_VALIDATE_EMAIL)) {
                        return $matches[0];
                    }
                    return Format::base64obfuscate("<a href=\"mailto:{$matches[0]}\">{$matches[0]}</a>");
                },
                $text
            );
            $fragment->appendXML($text);
            $node->parentNode->replaceChild($fragment, $node);
            return;
        }
        // process element nodes
        if ($node instanceof DOMElement) {
            if ($node->tagName == 'a') {
                return;
            }
            // Store children in array first
            $children = iterator_to_array($node->childNodes);
            foreach ($children as $child) {
                static::walk($child);
            }
        }
        // process fragment nodes
        if ($node instanceof DOMDocumentFragment) {
            $children = iterator_to_array($node->childNodes);
            foreach ($children as $child) {
                static::walk($child);
            }
        }
    }
}
