<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

class VideoServices
{
    protected static $init = false;
    protected static $services = [];
    protected static $parsers = [];

    public static function init()
    {
        if (static::$init) {
            return;
        }
        static::$init = true;
        //youtube
        static::addParser('youtube-full', [static::class, 'parse_youtube_full']);
        static::addParser('youtube-short', [static::class, 'parse_youtube_short']);
        static::addService('youtube', [static::class, 'service_youtube']);
        //facebook
        static::addParser('facebook', [static::class, 'parse_facebook']);
        static::addService('facebook', [static::class, 'service_facebook']);
    }

    public static function parse_facebook(string $url)
    {
        if ($parsed = static::parse_url($url)) {
            if ($parsed['host'] == 'www.facebook.com') {
                return ['facebook', $url];
            }
        }
        return null;
    }

    public static function parse_youtube_full(string $url)
    {
        if ($url = static::parse_url($url)) {
            if ($url['host'] == 'www.youtube.com' && $url['path'] == '/watch') {
                return @['youtube', $url['query']['v']];
            }
        }
        return null;
    }

    public static function parse_youtube_short(string $url)
    {
        if ($url = static::parse_url($url)) {
            if ($url['host'] == 'youtu.be') {
                return @['youtube', substr($url['path'], 1)];
            }
        }
        return null;
    }

    public static function service_facebook(string $id)
    {
        return '<iframe src="https://www.facebook.com/plugins/video.php?href=' . urlencode($id) . '&show_text=false" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allow="encrypted-media" allowFullScreen="true"></iframe>';
    }

    public static function service_youtube(string $id)
    {
        return '<iframe src="https://www.youtube-nocookie.com/embed/' . $id . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    }

    protected static function parse_url(string $url)
    {
        if ($url = parse_url($url)) {
            if (@$url['query']) {
                parse_str($url['query'], $url['query']);
            }
            return $url;
        } else {
            return null;
        }
    }

    public static function parse(string $url)
    {
        static::init();
        foreach (static::$parsers as $parser) {
            if ($parsed = $parser($url)) {
                return $parsed;
            }
        }
        return null;
    }

    public static function embed(string $service, string $id)
    {
        static::init();
        if ($service = @static::$services[$service]) {
            return $service($id);
        }
        return null;
    }

    public static function addParser(string $name, $fn)
    {
        static::$parsers[$name] = $fn;
    }

    public static function addService(string $name, $fn)
    {
        static::$services[$name] = $fn;
    }
}
