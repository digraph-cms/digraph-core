<?php

namespace DigraphCMS\DOM;

use DigraphCMS\Events\Dispatcher;
use DOMDocument;
use DOMElement;
use DOMNode;

class DOM
{
    public static function html(string $html, bool $fragment = null): string
    {
        if (!trim($html)) {
            return $html;
        }

        $fragment = $fragment ?? strpos($html,'<html') === false;

        // set up DOMDocument
        $dom = new DOMDocument();
        if (!@$dom->loadHTML($html, \LIBXML_NOERROR & \LIBXML_NOWARNING & \LIBXML_NOBLANKS)) {
            return $html;
        }
        // dispatch events
        static::dispatchEvents($dom, $fragment ? 'fragment' : 'full');

        //normalize and output to HTML
        $dom->normalizeDocument();
        if (!$fragment) {
            $html = $dom->saveHTML();
        } else {
            $html = static::bodyOnly($dom);
            if ($html === null) {
                $html = $dom->saveHTML();
            }
        }
        //fix self-closing tags that aren't actually allowed to self-close in HTML
        $html = preg_replace('@(<(a|script|noscript|table|iframe|noframes|canvas|style)[^>]*)/>@ims', '$1></$2>', $html);
        //fix non-self-closing tags that are supposed to self-close
        $html = preg_replace('@(<(source)[^>]*)></\2>@ims', '$1 />', $html);
        // return processed HTML
        return $html;
    }

    protected static function bodyOnly(DOMNode $dom): ?string
    {
        if ($dom instanceof DOMElement) {
            if ($dom->tagName == 'body') {
                $out = '';
                foreach ($dom->childNodes as $c) {
                    $out .= $dom->ownerDocument->saveHTML($c);
                }
                return $out;
            }
        }
        foreach ($dom->childNodes ?? [] as $c) {
            if ($out = static::bodyOnly($c)) {
                return $out;
            }
        }
        return null;
    }

    /**
     * Dispatch events on a given DOM node, recurse into children.
     *
     * @param DOMNode $node
     * @return void
     */
    protected static function dispatchEvents(DOMNode $node, string $phase)
    {
        //pick event name if applicable
        $eventNames = [];
        if ($node instanceof \DOMElement) {
            //skip events on elements with data-dom-events="off"
            if ($node->getAttribute('data-dom-events') == 'off') {
                return;
            }
            //onDOMElement_{tagname} event name
            $eventNames[] = 'onDOMElement_' . $node->tagName;
            $eventNames[] = 'onDOMElement_' . $node->tagName . '_' . $phase;
        } elseif ($node instanceof \DOMComment) {
            //onDOMComment event name
            $eventNames[] = 'onDOMComment';
            $eventNames[] = 'onDOMComment_' . $phase;
        } elseif ($node instanceof \DOMText) {
            $eventNames[] = 'onDOMText';
            $eventNames[] = 'onDOMText_' . $phase;
        }
        //dispatch event if necessary
        foreach ($eventNames as $eventName) {
            $event = new DOMEvent($node);
            Dispatcher::dispatchEvent($eventName, [$event]);
            //do deletion if event calls for it
            if ($event->getDelete()) {
                $node->parentNode->removeChild($node);
            }
            //else do replacement if event calls for it
            elseif ($html = $event->getReplacement()) {
                $newNode = $node->ownerDocument->createDocumentFragment();
                @$newNode->appendXML($html);
                $node->parentNode->replaceChild($newNode, $node);
                $node = $newNode;
                static::dispatchEvents($newNode, $phase);
            }
        }
        //recurse into children if found
        if ($node && $node->hasChildNodes()) {
            //build an array of children, disconnected from childNodes object
            //we need to do this so we can replace them without breaking the
            //order and total coverage of looping through them
            $children = [];
            foreach ($node->childNodes as $child) {
                $children[] = $child;
            }
            //loop through new array of child nodes
            foreach ($children as $child) {
                static::dispatchEvents($child, $phase);
            }
        }
    }
}
