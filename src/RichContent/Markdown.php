<?php

namespace DigraphCMS\RichContent;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use N0sz\CommonMark\Marker\MarkerExtension;

class Markdown
{
    public static function parse(string $input): string
    {
        static $converter = null;
        if (!$converter) {
            $config = [
                'safe' => false,
                'html_input' => 'allow',
                'disallowed_raw_html' => [
                    'disallowed_tags' => ['title', 'textarea', 'style', 'xmp', 'noembed', 'noframes', 'script', 'plaintext'],
                ],
            ];
            $environment = new Environment($config);
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new GithubFlavoredMarkdownExtension());
            $environment->addExtension(new MarkerExtension());
            $converter = new MarkdownConverter($environment);
        }
        return $converter->convert($input);
    }
}
