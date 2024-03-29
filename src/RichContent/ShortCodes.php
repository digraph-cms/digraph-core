<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Events\Dispatcher;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

// Whenever this class is loaded, also set up the built-in shortcodes event listener class
Dispatcher::addSubscriber(ShortCodesListener::class);

class ShortCodes
{
    public static function parse(string $string): string
    {
        return static::parser()->process($string);
    }

    protected static function parser(): Processor
    {
        static $parser;
        if (!$parser) {
            $handlers = new HandlerContainer();
            $handlers->setDefault(function (ShortcodeInterface $s) {
                // return processed tag content if found
                if ($content = static::codeHandler($s)) {
                    return $content;
                }
                // otherwise try to return the original text
                if ($s instanceof ProcessedShortcode) {
                    return $s->getShortcodeText();
                }
                // otherwise insert content with tag stripped
                return $s->getContent();
            });
            $parser = new Processor(new RegularParser(), $handlers);
        }
        return $parser;
    }

    protected static function codeHandler(ShortcodeInterface $s): ?string
    {
        return Dispatcher::firstValue('onShortCode', [$s])
            ?? Dispatcher::firstValue('onShortCode_' . $s->getName(), [$s])
            ?? $s->getContent()
            ?? null;
    }
}
