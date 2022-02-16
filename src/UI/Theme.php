<?php

namespace DigraphCMS\UI;

use DigraphCMS\Config;
use DigraphCMS\Digraph;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Media\CSS;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\Media\Media;
use DigraphCMS\Session\Cookies;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Users;
use OzdemirBurak\Iris\Color\Hex;
use OzdemirBurak\Iris\Color\Rgba;

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
        'external_css' => [],
        'internal_css' => [
            '/styles/*.css'
        ],
        'blocking_js' => [
            '/scripts_blocking/*.js'
        ],
        'async_js' => [
            '/scripts/*.js'
        ],
        'variables' => [
            'light' => [
                'background' => '#eeeeee',
                'background-light' => '#fafafa',
                'background-lighter' => '#ffffff',
                'color' => '#333333',
                'grid' => '1rem',
                'line-length' => '35em',
                'line-height' => '1.4',
                'line-height-tight' => '1.2',
                'shadow' => 'calc(var(--grid)/8) calc(var(--grid)/4) var(--grid) rgba(0,0,0,0.05)',
                'shadow-inset' => 'inset -calc(var(--grid)/8) -calc(var(--grid)/4) var(--grid) rgba(0,0,0,0.05)',
                'border' => '2px',
                'border-radius' => '4px',
                'font' => [
                    'content' => 'serif',
                    'header' => 'sans-serif',
                    'ui' => 'sans-serif',
                    'code' => 'monospace',
                ],
                'link' => [
                    'normal' => '#1976d2',
                    'visited' => '#512da8',
                    'focus' => '#f57c00',
                    'active' => '#d32f2f'
                ],
                'cue' => [
                    'interactive' => '#039BE5',
                    'information' => '#0097A7',
                    'safe' => '#00C853',
                    'warning' => '#FFAB00',
                    'danger' => '#DD2C00'
                ],
                'theme' => [
                    'neutral' => '#9e9e9e',
                    'accent' => '#006064',
                    'highlight' => '#00b8d4'
                ]
            ],
            'dark' => [
                'background' => '#222222',
                'color' => '#fff',
                'shadow' => 'calc(var(--grid)/8) calc(var(--grid)/4) var(--grid) rgba(0,0,0,0.2)',
                'shadow-inset' => 'inset -calc(var(--grid)/8) -calc(var(--grid)/4) var(--grid) rgba(0,0,0,0.2)',
                'link' => [
                    'normal' => '#64b5f6',
                    'visited' => '#ba68c8',
                    'focus' => '#ffb74d',
                    'active' => '#e57373'
                ]
            ],
            'colorblind' => [
                'cue' => [
                    'interactive' => '#0091EA',
                    'information' => '#006064',
                    'safe' => '#2196f3',
                    'warning' => '#ff5722',
                    'danger' => '#ff5722'
                ]
            ],
            'colorblind_dark' => [
                'cue' => [
                    'interactive' => '#0091EA',
                    'information' => '#006064',
                    'safe' => '#2196f3',
                    'warning' => '#ff5722',
                    'danger' => '#ff5722'
                ]
            ]
        ]
    ];
    protected static $scssVars = [];
    protected static $variables = [];
    protected static $variables_cache;
    protected static $blockingThemeCss = [];
    protected static $blockingPageCss = [];
    protected static $externalThemeCss = [];
    protected static $externalPageCss = [];
    protected static $internalThemeCss = [];
    protected static $internalPageCss = [];
    protected static $blockingThemeJs = [];
    protected static $blockingPageJs = [];
    protected static $asyncThemeJs = [];
    protected static $asyncPageJs = [];
    protected static $inlinePageJs = [];
    protected static $bodyClasses = [];

    public static function addBodyClass(string $class)
    {
        if (!in_array($class, static::$bodyClasses)) {
            static::$bodyClasses[] = $class;
        }
    }

    public static function colorMode(): ?string
    {
        if ($user = Users::current()) {
            if ($user['ui.colormode']) {
                return $user['ui.colormode'];
            }
        }
        return @Cookies::get('ui', 'color')['color'];
    }

    public static function colorblindMode(): ?string
    {
        if ($user = Users::current()) {
            if ($user['ui.colorblindmode'] !== null) {
                return $user['ui.colorblindmode'];
            }
        }
        return @Cookies::get('ui', 'color')['colorblindmode'];
    }

    public static function bodyClasses(): array
    {
        $classes = static::$bodyClasses;
        if ($mode = static::colorMode()) {
            $classes[] = 'colors--' . $mode;
        }
        if (static::colorblindMode()) {
            $classes[] = 'colors--colorblind';
        }
        return $classes;
    }

    public static function variables(string $mode = 'light'): array
    {
        if (static::$variables_cache === null) {
            static::$variables_cache = static::compileVariables(
                static::$variables,
                strpos($mode, 'dark') !== false
            );
        }
        return static::$variables_cache[$mode] ?? [];
    }

    public static function renderVariableCss()
    {
        $template = __DIR__ . '/variables.css';
        $file = new DeferredFile(
            'variables.css',
            function (DeferredFile $file) use ($template) {
                $css = file_get_contents($template);
                $css = preg_replace_callback(
                    '@\/\*\!variables\((.+?)\)\*\/@',
                    function ($m) {
                        $variables = static::variables($m[1]);
                        $lines = [];
                        foreach ($variables as $k => $v) {
                            $lines[] = "--$k: $v;";
                        }
                        return implode(PHP_EOL, $lines);
                    },
                    $css
                );
                $css = CSS::css($css);
                file_put_contents($file->path(), $css);
            },
            [
                filemtime($template),
                static::$variables
            ]
        );
        $file->write();
        printf(
            '<link rel="stylesheet" href="%s" />' . PHP_EOL,
            $file->url()
        );
    }

    protected static function compileVariables(array $variables, $invertColorVariations): array
    {
        foreach ($variables as $mode => $vs) {
            $variables[$mode] = static::compileVariableList($vs, '', $invertColorVariations);
        }
        return $variables;
    }

    protected static function compileVariableList(array $variables, $prefix = '', $invertColorVariations = false): array
    {
        $output = [];
        foreach ($variables as $k => $v) {
            $k = $prefix ? "$prefix-$k" : $k;
            // recurse into arrays
            if (is_array($v)) {
                foreach (static::compileVariableList($v, $k, $invertColorVariations) as $k => $v) {
                    $output[$k] = $v;
                }
            }
            // otherwise prepare color variations/complements
            elseif (preg_match("/#[0-9a-f]{6}/i", $v)) {
                $output[$k] = $v;
                $output["$k-inv"] = static::contrastColor(new Hex($v));
                if (!preg_match('/-((light|dark)(er)?|bright(er)?)$/', $k)) {
                    foreach (static::prepareColorVariations($v, $invertColorVariations) as $t => $v) {
                        $output["$k-$t"] = $v->__toString();
                    }
                }
            }
            // otherwise copy values
            else {
                $output[$k] = $v;
            }
        }
        return $output;
    }

    protected static function prepareColorVariations($color, $invert)
    {
        if (!$invert) {
            // normal meaning of light/dark
            $colors = [
                'light' => (new Hex($color))->lighten(2),
                'dark' => (new Hex($color))->darken(2),
                'lighter' => (new Hex($color))->lighten(4),
                'darker' => (new Hex($color))->darken(4),
                'bright' => (new Hex($color))->brighten(15),
            ];
        } else {
            // inverted for dark mode
            $colors = [
                'dark' => (new Hex($color))->lighten(2),
                'light' => (new Hex($color))->darken(2),
                'darker' => (new Hex($color))->lighten(4),
                'lighter' => (new Hex($color))->darken(4),
                'bright' => (new Hex($color))->brighten(15),
            ];
        }
        // add alpha colors
        $colors['a90'] = (new Hex($color))->toRgba()->alpha(0.9);
        $colors['a80'] = (new Hex($color))->toRgba()->alpha(0.8);
        $colors['a50'] = (new Hex($color))->toRgba()->alpha(0.5);
        $colors['a20'] = (new Hex($color))->toRgba()->alpha(0.2);
        $colors['a10'] = (new Hex($color))->toRgba()->alpha(0.1);
        return $colors;
    }

    protected static function contrastColor($color)
    {
        if ($color->isLight()) {
            return new Rgba('rgba(0,0,0,0.95)');
        } else {
            return new Rgba('rgba(255,255,255,0.95)');
        }
    }

    /**
     * Reset all theme (but not page) assets to the default theme.
     *
     * @param array|string|null $activeThemes
     * @return void
     */
    public static function resetTheme($activeThemes = null)
    {
        static::$variables_cache = null;
        static::$scssVars = static::themeConfig($activeThemes, 'scss_vars');
        static::$variables = static::themeConfig($activeThemes, 'variables');
        static::$blockingThemeCss = static::themeConfig($activeThemes, 'blocking_css');
        static::$externalThemeCss = static::themeConfig($activeThemes, 'external_css');
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
        static::$externalPageCss = [];
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

    public static function addExternalThemeCss($url)
    {
        static::$externalThemeCss[] = $url;
    }

    public static function addExternalPageCss($url)
    {
        static::$externalPageCss[] = $url;
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
    protected static function renderJs(string $name, array $urls_or_files, bool $async)
    {
        // identify all the files we need to include
        /** @var File[] */
        $files = [];
        foreach ($urls_or_files as $url_or_file) {
            if (basename($url_or_file) == '*.js') {
                // search and recurse if the filename is *.js
                $files = array_merge(
                    $files,
                    array_map(
                        Media::class . '::get',
                        Media::globToPaths($url_or_file, $async)
                    )
                );
            } else {
                if (is_string($url_or_file)) {
                    if (preg_match('@^(https?)?//@', $url_or_file)) {
                        // embed external stuff immediately
                        $url = $url_or_file;
                    } else {
                        // get media files for internal stuff so it can be bundled or embedded
                        $r = $url_or_file;
                        $url_or_file = Media::get($url_or_file);
                        if (!$url_or_file) {
                            throw new HttpError(500, 'JS file ' . $r . ' not found');
                        }
                        $files[] = $url_or_file;
                    }
                }
            }
        }
        if (!$files) {
            return;
        }
        if (!Config::get('theme.bundle_js')) {
            // embed files individually
            echo "<!-- $name -->";
            foreach ($files as $file) {
                // render script tag
                echo "<script src='" . $file->url() . "'";
                if ($async) {
                    echo " async";
                }
                echo "></script>" . PHP_EOL;
            }
        } else {
            // bundle scripts
            $file = new DeferredFile(
                "$name.js",
                function (DeferredFile $file) use ($files, $name) {
                    $content = "";
                    foreach ($files as $f) {
                        $content .= $f->content() . PHP_EOL . ';';
                    }
                    file_put_contents($file->path(), $content);
                },
                array_map(
                    function (File $f) {
                        return $f->identifier();
                    },
                    $files
                )
            );
            $file->write();
            echo "<script src='" . $file->url() . "'";
            if ($async) {
                echo " async";
            }
            echo "></script>" . PHP_EOL;
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

    protected static function renderExternalCss()
    {
        foreach (array_merge(static::$externalThemeCss, static::$externalPageCss) as $url) {
            echo "<link rel='stylesheet' href='" . $url . "'>" . PHP_EOL;
        }
    }

    protected static function renderInternalCss(string $name, array $urls)
    {
        if (!Config::get('theme.bundle_css')) {
            $files = [];
            foreach ($urls as $url) {
                if (preg_match('/\/\*\.css$/', $url)) {
                    //wildcard search
                    $url = new URL($url);
                    foreach (Media::search(preg_replace('/\.s?css$/', '.{scss,css}', $url->path())) as $file) {
                        $files[] = $url->directory() . basename($file);
                    }
                } else {
                    //normal single file
                    $files[] = $url;
                }
            }
            $files = array_filter($files);
            foreach ($files as $file) {
                if ($file = Media::get($file)) {
                    echo "<link rel='stylesheet' href='" . $file->url() . "'>" . PHP_EOL;
                }
            }
        } else {
            $files = [];
            foreach ($urls as $url) {
                if (preg_match('/\/\*\.css$/', $url)) {
                    //wildcard search
                    $url = new URL($url);
                    foreach (Media::search(preg_replace('/\.s?css$/', '.{scss,css}', $url->path())) as $file) {
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
            'origin' => $origin,
            'uuidChars' => Digraph::uuidChars(),
            'uuidPattern' => Digraph::uuidPattern()
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
        static::renderVariableCss();
        static::renderBlockingCss();
        static::renderExternalCss();
        static::renderInternalCss('theme', static::$internalThemeCss);
        static::renderInternalCss('page', static::$internalPageCss);
        // render core js
        static::renderCoreJs();
        // render js
        static::renderJs('theme_blocking', static::$blockingThemeJs, false);
        static::renderJs('page_blocking', static::$blockingPageJs, false);
        static::renderJs('theme_async', static::$asyncThemeJs, true);
        static::renderJs('page_async', static::$asyncPageJs, true);
        static::renderInlineJs(static::$inlinePageJs);
        return ob_get_clean();
    }
}
