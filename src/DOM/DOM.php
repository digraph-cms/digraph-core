<?php

namespace DigraphCMS\DOM;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\Events\Dispatcher;
use DOMNode;
use Masterminds\HTML5;

class DOM
{

    public static function html(string $html, bool $fragment = null): string
    {
        if (!trim($html)) {
            return $html;
        }

        $fragment = $fragment ?? strpos($html, '<html') === false;

        return Cache::get(
            'dom/html/' . md5(serialize([$html,$fragment])),
            function () use ($html, $fragment) {
                // parse fragment
                if ($fragment) {
                    $dom = static::html5()->parseFragment($html);
                    static::dispatchEvents($dom, 'fragment');
                    $html = static::html5()->saveHTML($dom);
                }
                // parse full document
                else {
                    $dom = static::html5()->loadHTML($html);
                    static::dispatchEvents($dom, 'full');
                    $dom->normalizeDocument();
                    $html = static::html5()->saveHTML($dom);
                }
                // fix oddities
                // $html = str_ireplace(
                //     ['<br></br>'],
                //     ['<br/>'],
                //     $html
                // );
                // return processed HTML
                return $html;
            },
            Config::get('cache.dom_ttl')
        );
    }

    protected static function html5(): HTML5
    {
        static $html5;
        return $html5 ?? $html5 = new HTML5();
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
        if ($node->hasChildNodes()) {
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
