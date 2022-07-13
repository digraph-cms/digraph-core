<?php

namespace DigraphCMS\RichContent\Video;

use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\IFRAME;

class VideoEmbed extends DIV
{
    protected $service, $src;
    protected $inner, $iframe;

    public static function fromURL(string $url): ?VideoEmbed
    {
        $url = parse_url($url);
        if (!$url) return null;
        if (@$url['query']) parse_str($url['query'], $url['query']);
        return Dispatcher::firstValue('onVideoEmbed', [$url]);
    }

    public static function onVideoEmbed(array $url): ?VideoEmbed
    {
        if ($url['host'] == 'www.youtube.com' && $url['path'] == '/watch') {
            return new YouTubeVideo($url['query']['v']);
        } elseif ($url['host'] == 'youtu.be') {
            return new YouTubeVideo(substr($url['path'], 1));
        }
        return null;
    }

    public function __construct(string $service, string $src)
    {
        $this->service = $service;
        $this->src = $src;
        $this->addClass('video-embed');
    }

    public function children(): array
    {
        return [$this->inner()];
    }

    protected function inner(): DIV
    {
        if (!$this->inner) {
            $this->inner = new DIV;
            $this->inner->addClass('video-embed__inner');
            $this->inner->addChild($this->iframe());
        }
        return $this->inner;
    }

    protected function iframe(): IFRAME
    {
        if (!$this->iframe) {
            $this->iframe = new IFRAME($this->src());
            $this->iframe->addClass('video-embed__iframe');
            $this->iframe->setAttribute('allowfullscreen', true);
            $this->iframe->setAttribute('frameborder', '0');
        }
        return $this->iframe;
    }

    public function service(): string
    {
        return $this->service;
    }

    public function src(): string
    {
        return $this->src;
    }
}

Dispatcher::addSubscriber(VideoEmbed::class);
