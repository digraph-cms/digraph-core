<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Events\Dispatcher;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
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
                return static::codeHandler($s);
            });
            $parser = new Processor(new RegularParser(), $handlers);
        }
        return $parser;
    }

    protected static function codeHandler(ShortcodeInterface $s): string
    {
        return Dispatcher::firstValue('onShortCode', [$s])
            ?? Dispatcher::firstValue('onShortCode_' . $s->getName(), [$s])
            ?? $s->getContent()
            ?? '';
    }
}
