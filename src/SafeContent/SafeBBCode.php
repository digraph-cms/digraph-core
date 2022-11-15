<?php

namespace DigraphCMS\SafeContent;

use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\Theme;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SafeBBCode
{
    public static function loadEditorMedia()
    {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;
        Theme::addBlockingPageCss('/safe_bbcode_editor/*.css');
        Theme::addBlockingPageJs('/node_modules/sceditor/minified/sceditor.min.js');
        Theme::addBlockingPageJs('/node_modules/sceditor/minified/formats/bbcode.js');
        Theme::addBlockingPageJs('/node_modules/sceditor/minified/plugins/autosave.js');
        Theme::addBlockingPageJs('/node_modules/sceditor/minified/plugins/autoyoutube.js');
        Theme::addBlockingPageJs('/node_modules/sceditor/minified/plugins/plaintext.js');
        Theme::addBlockingPageJs('/safe_bbcode_editor/*.js');
    }

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
        return Dispatcher::firstValue('onBBCode', [$s])
            ?? Dispatcher::firstValue('onBBCode_' . $s->getName(), [$s])
            ?? $s->getContent()
            ?? null;
    }
}
