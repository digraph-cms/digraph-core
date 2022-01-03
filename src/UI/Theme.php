<?php

namespace DigraphCMS\UI;

use DigraphCMS\Config;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Media\CSS;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\Media\Media;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;

Theme::resetTheme();
Theme::resetPage();

/**
 * Manages which CSS/JS/Media are included in the final output after output
 * is templated into a final HTML page. Also responsible for bundling media
 * files where appropriate/configured and for generating the final HTML used
 * to embed things in the HEAD tag and and the end of the BODY tag.
 */
class Theme
{
    protected static $core = [
        'blocking_css' => [
            '/styles_blocking/*.css'
        ],
        'internal_css' => [
            '/styles/*.css'
        ],
        'blocking_js' => [
            '/scripts_blocking/*.js'
        ],
        'async_js' => [
            '/scripts/*.js'
        ],
        'css_vars' => [
            'body-bg' => '#fafafa',
            'body-fg' => '#333',
            'dark-body-bg' => '#222',
            'dark-body-fg' => '#fff',
            'grid-unit' => '1rem',
            'font-content' => 'serif',
            'font-header' => 'sans-serif',
            'font-ui' => 'sans-serif',
            'font-code' => 'monospace',
            'border-unit' => '2px',
            'border-radius' => '6px',
            'color-neutral' => '#BDC3C7',
            'color-accent' => '#34495E',
            'color-highlight' => '#1ABC9C',
            'color-link' => '#2980B9',
            'color-link-visited' => '#8E44AD',
            'color-link-focus' => '#D35400',
            'color-link-hover' => '#D35400',
            'color-link-active' => '#C0392B',
            'color-info' => '#2980B9',
            'color-confirmation' => '#27AE60',
            'color-warning' => '#D35400',
            'color-error' => '#C0392B',
            'color-brand-facebook' => '#1877F2',
            'color-brand-twitter' => '#1DA1F2',
            'color-brand-linkedin' => '#0A66C2',
            'color-brand-skype' => '#00AFF0',
            'color-brand-dropbox' => '#0061FF',
            'color-brand-vimeo' => '#1AB7EA',
            'color-brand-tumblr' => '#34465D',
            'color-brand-pinterest' => '#BD081C',
            'color-brand-youtube' => '#CD201F',
            'color-brand-reddit' => '#FF5700',
            'color-brand-quora' => '#B92B27',
            'color-brand-yelp' => '#AF0606',
            'color-brand-weibo' => '#DF2029',
            'color-brand-hackernews' => '#FF6600',
            'color-brand-soundcloud' => '#FF3300',
            'color-brand-blogger' => '#F57D00',
            'color-brand-snapchat' => '#FFFC00',
            'color-brand-whatsapp' => '#25D366',
            'color-brand-wechat' => '#09B83E',
            'color-brand-medium' => '#02B875',
            'color-brand-vine' => '#00B489',
            'color-brand-slack' => '#3AAF85',
            'color-brand-dribbble' => '#E4405F',
            'color-brand-flickr' => '#FF0084',
            'color-brand-foursquare' => '#F94877',
            'color-brand-tiktok' => '#EE1D51',
            'color-brand-behance' => '#131418'
        ]
    ];
    protected static $cssVars = [];
    protected static $blockingThemeCss = [];
    protected static $blockingPageCss = [];
    protected static $internalThemeCss = [];
    protected static $internalPageCss = [];
    protected static $blockingThemeJs = [];
    protected static $blockingPageJs = [];
    protected static $asyncThemeJs = [];
    protected static $asyncPageJs = [];
    protected static $inlinePageJs = [];

    public static function cssVars(): array
    {
        return static::$cssVars;
    }

    public static function cssVars_css(): string
    {
        if (static::$cssVars) {
            $out = ':root {' . PHP_EOL;
            foreach (static::$cssVars as $k => $v) {
                $out .= "--$k: $v";
            }
            $out .= '}' . PHP_EOL;
            return $out;
        } else {
            return '';
        }
    }

    public static function cssVar(string $name, $value = null)
    {
        if ($value !== null) {
            static::$cssVars[$name] = $value;
        }
        return @static::$cssVars[$name];
    }

    /**
     * Reset all theme (but not page) assets to the default theme.
     *
     * @param array|string|null $activeThemes
     * @return void
     */
    public static function resetTheme($activeThemes = null)
    {
        static::$cssVars = static::themeConfig($activeThemes, 'css_vars');
        static::$blockingThemeCss = static::themeConfig($activeThemes, 'blocking_css');
        static::$internalThemeCss = static::themeConfig($activeThemes, 'internal_css');
        static::$blockingThemeJs = static::themeConfig($activeThemes, 'blocking_js');
        static::$asyncThemeJs = static::themeConfig($activeThemes, 'async_js');
    }

    /**
     * Reset all page assets to their default (probably nothing)
     *
     * @return void
     */
    public static function resetPage()
    {
        static::$blockingPageCss = [];
        static::$internalPageCss = [];
        static::$blockingPageJs = [];
        static::$asyncPageJs = [];
    }

    protected static function themeConfig($activeThemes, $section): array
    {
        if ($activeThemes === null) {
            $activeThemes = Config::get('theme.active_themes') ?? [];
        }
        if (is_string($activeThemes)) {
            $activeThemes = [$activeThemes];
        }
        $value = @static::$core[$section] ?? [];
        $value = array_replace_recursive($value, Config::get("themes.core.$section") ?? []);
        foreach ($activeThemes as $theme) {
            $value = array_replace_recursive($value, Config::get("themes.$theme.$section") ?? []);
        }
        $value = array_replace_recursive($value, Config::get("themes.override.$section") ?? []);
        return $value;
    }

    public static function addBlockingThemeCss($url)
    {
        static::$blockingThemeCss[] = $url;
    }

    public static function addBlockingPageCss($url)
    {
        static::$blockingPageCss[] = $url;
    }

    public static function addInternalThemeCss($url)
    {
        static::$internalThemeCss[] = $url;
    }

    public static function addInternalPageCss($url)
    {
        static::$internalPageCss[] = $url;
    }

    public static function addBlockingThemeJs($url_or_file)
    {
        static::$blockingThemeJs[] = $url_or_file;
    }

    public static function addBlockingPageJs($url_or_file)
    {
        static::$blockingPageJs[] = $url_or_file;
    }

    public static function addThemeJs($url_or_file)
    {
        static::$asyncThemeJs[] = $url_or_file;
    }

    public static function addPageJs($url_or_file)
    {
        static::$asyncPageJs[] = $url_or_file;
    }

    public static function addInlinePageJs($string_or_file)
    {
        static::$inlinePageJs[] = $string_or_file;
    }

    /**
     * @param File[]|string[] $urls_or_files
     * @param boolean $async
     * @return void
     */
    protected static function renderJs(array $urls_or_files, bool $async)
    {
        foreach ($urls_or_files as $url_or_file) {
            if (basename($url_or_file) == '*.js') {
                // search and recurse if the filename is *.js
                static::renderJs(Media::globToPaths($url_or_file), $async);
            } else {
                // otherwise render script tag
                if (is_string($url_or_file)) {
                    if (preg_match('@^(https?)?//@', $url_or_file)) {
                        $url = $url_or_file;
                    } else {
                        $r = $url_or_file;
                        $url_or_file = Media::get($url_or_file);
                        if (!$url_or_file) {
                            throw new HttpError(500, 'JS file ' . $r . ' not found');
                        }
                    }
                }
                if ($url_or_file instanceof File) {
                    $url = $url_or_file->url();
                }
                echo "<script src='$url'";
                if ($async) {
                    echo " async";
                }
                echo "></script>" . PHP_EOL;
            }
        }
    }

    /**
     * @param File[]|string[] $strings_or_files
     * @return void
     */
    protected static function renderInlineJs(array $strings_or_files)
    {
        foreach ($strings_or_files as $string_or_file) {
            if (basename($string_or_file) == '*.js') {
                // recurse if filename is *.js
                static::renderInlineJs(Media::globToPaths($string_or_file));
            } else {
                // print script inline
                if ($string_or_file instanceof File) {
                    $string_or_file = $string_or_file->content();
                }
                echo "<script>";
                echo $string_or_file;
                echo "</script>" . PHP_EOL;
            }
        }
    }

    protected static function renderBlockingCss()
    {
        $sourceMapping = Config::get('files.css.sourcemap');
        Config::set('files.css.sourcemap', false);
        $files = [];
        foreach (array_merge(static::$blockingThemeCss, static::$blockingPageCss) as $url) {
            if (preg_match('/\/\*\.css$/', $url)) {
                //wildcard search
                foreach (Media::glob(preg_replace('/\.css$/', '.{scss,css}', $url)) as $file) {
                    $files[] = $file;
                }
            } else {
                //normal single file
                $url = new URL($url);
                $files[] = Media::get($url->path());
            }
        }
        $files = array_filter($files);
        if ($files) {
            echo "<style>" . PHP_EOL;
            foreach ($files as $file) {
                echo $file->content() . PHP_EOL;
            }
            echo "</style>" . PHP_EOL;
        }
        Config::set('files.css.sourcemap', $sourceMapping);
    }

    protected static function renderInternalCss(string $name, array $urls)
    {
        if (!Config::get('theme.bundle_css')) {
            $files = [];
            foreach ($urls as $url) {
                if (preg_match('/\/\*\.css$/', $url)) {
                    //wildcard search
                    foreach (Media::glob(preg_replace('/\.css$/', '.{scss,css}', $url)) as $file) {
                        $files[] = $file;
                    }
                } else {
                    //normal single file
                    $url = new URL($url);
                    $files[] = Media::get($url->path());
                }
            }
            $files = array_filter($files);
            foreach ($files as $file) {
                echo "<link rel='stylesheet' href='" . $file->url() . "'>" . PHP_EOL;
            }
        } else {
            $files = [];
            foreach ($urls as $url) {
                if (preg_match('/\/\*\.css$/', $url)) {
                    //wildcard search
                    $url = new URL($url);
                    foreach (Media::search(preg_replace('/\.css$/', '.{scss,css}', $url->path())) as $file) {
                        $files[] = $url->directory() . basename($file);
                    }
                } else {
                    //normal single file
                    $files[] = $url;
                }
            }
            $files = array_filter($files);
            $file = new DeferredFile($name . '.css', function (DeferredFile $file) use ($files) {
                file_put_contents(
                    $file->path(),
                    CSS::scss(
                        implode(
                            PHP_EOL,
                            array_map(
                                function (string $path): string {
                                    return "@import \"$path\";";
                                },
                                $files
                            )
                        )
                    )
                );
            }, [$urls]);
            $file->write();
            echo "<link rel='stylesheet' href='" . $file->url() . "'>" . PHP_EOL;
        }
    }

    protected static function coreConfig(): array
    {
        $origin = parse_url(URLs::site());
        $origin = implode('', [
            @$origin['scheme'] ?? 'http',
            '://',
            @$origin['host'],
            @$origin['port'] ? ':' . $origin['port'] : ''
        ]);
        $config = [
            'url' => URLs::site(),
            'origin' => $origin
        ];
        return $config;
    }

    protected static function renderCoreJs()
    {
        $script = Media::get('/scripts_blocking/core/core.js')->content();
        $file = new DeferredFile(
            'core.js',
            function (DeferredFile $file) use ($script) {
                $script .= PHP_EOL . PHP_EOL . 'Digraph.config = ' . Format::js_encode_object(static::coreConfig());
                file_put_contents($file->path(), $script);
            },
            [
                static::coreConfig(),
                $script
            ]
        );
        $file->write();
        echo "<script src=\"" . $file->url() . "\"></script>" . PHP_EOL;
    }

    /**
     * Generates the markup to embed all linked media in the HEAD tag
     *
     * @return string
     */
    public static function head(): string
    {
        ob_start();
        // render css
        static::renderBlockingCss();
        static::renderInternalCss('theme', static::$internalThemeCss);
        static::renderInternalCss('page', static::$internalPageCss);
        // render core js
        static::renderCoreJs();
        // render js
        static::renderJs(static::$blockingThemeJs, false);
        static::renderJs(static::$blockingPageJs, false);
        static::renderJs(static::$asyncThemeJs, true);
        static::renderJs(static::$asyncPageJs, true);
        static::renderInlineJs(static::$inlinePageJs);
        return ob_get_clean();
    }
}
