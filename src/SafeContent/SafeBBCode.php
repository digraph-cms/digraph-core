<?php

namespace DigraphCMS\SafeContent;

use DigraphCMS\HTML\A;
use DigraphCMS\RichContent\Video\VideoEmbed;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Theme;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ProcessedShortcode;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SafeBBCode
{
    const TAG_TO_TAGS = [
        'b' => 'strong',
        'i' => 'em',
        'u' => 'ins',
        's' => 'del',
        'ul' => 'ul',
        'ol' => 'ol',
        'li' => 'li',
        'quote' => 'blockquote',
    ];

    const NUISANCE_TAGS = [
        'size',
    ];

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

    /**
     * @return ($string is null ? null : string)
     */
    public static function stripNuisanceTags(string|null $string): string|null
    {
        if (is_null($string)) return null;
        // create a special parser just for this
        static $parser;
        if (!$parser) {
            $handlers = new HandlerContainer();
            foreach (static::NUISANCE_TAGS as $tag) {
                $handlers->add($tag, function (ShortcodeInterface $s) {
                    return $s->getContent();
                });
            }
            $parser = new Processor(new RegularParser(), $handlers);
        }
        // process the string
        return $parser->process($string);
    }

    public static function parse(string $string): string
    {
        $string = Sanitizer::full($string);
        $string = static::parser()->process($string);
        $string = str_replace("\r\n", "<br>", $string);
        $string = str_replace("\n", "<br>", $string);
        $string = SafeBBCodeHtmlParser::parse($string);
        $string = "<div class='safe-bbcode-content'>$string</div>";
        return $string;
    }

    protected static function parser(): Processor
    {
        static $parser;
        if (!$parser) {
            $handlers = new HandlerContainer();
            // named handlers for stripping nuisance tags
            foreach (static::NUISANCE_TAGS as $tag) {
                $handlers->add($tag, function (ShortcodeInterface $s) {
                    return $s->getContent();
                });
            }
            // named handlers for tags-to-tags straight conversion
            foreach (static::TAG_TO_TAGS as $tag => $htmlTag) {
                $handlers->add($tag, function (ShortcodeInterface $s) use ($htmlTag) {
                    return sprintf('<%1$s>%2$s</%1$s>', $htmlTag, $s->getContent());
                });
            }
            // default handler for fancier stuff
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

    protected static function tag_youtube(ShortcodeInterface $s): ?string
    {
        return VideoEmbed::fromURL('https://youtu.be/' . trim($s->getContent()));
    }

    protected static function tag_url(ShortcodeInterface $s): ?string
    {
        $url = $s->getBbCode();
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // set up URL
            $link = (new A)
                ->setAttribute('href', $url)
                ->setAttribute('rel', 'nofollow')
                ->addChild($s->getContent() ? $s->getContent() : preg_replace('/^(https?:)?\/\//', '', $url));
            // return built link
            return $link;
        }
        return null;
    }

    public static function tag_email(ShortcodeInterface $s): ?string
    {
        $email = $s->getBbCode() ?? $s->getContent();
        $content = $s->getContent() ? $s->getContent() : $email;
        return Format::base64obfuscate(sprintf('<a href="mailto:%s">%s</a>', $email, $content));
    }

    /**
     * Note that unlike the handlers for full-on shortcodes in rich content,
     * this class does not use the global Dispatcher. Safe BBCode is intended to
     * be just that: safe. It is not extensible, so that it cannot be made any
     * less safe than the default implementation.
     *
     * @param ShortcodeInterface $s
     * @return string|null
     */
    protected static function codeHandler(ShortcodeInterface $s): ?string
    {
        // handle more advanced tags
        $fn = 'tag_' . $s->getName();
        if (method_exists(static::class, $fn)) return call_user_func([static::class, $fn], $s);
        else return null;
    }
}
