<?php

namespace DigraphCMS\Media;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;

// Always add the default system media directory
Media::addSource(__DIR__ . '/../../media');
// Add self as event subscriber
Dispatcher::addSubscriber(Media::class);

class Media
{
    protected static $sources = [];

    /**
     * Add a source directory to the top of the list of directories to search in
     * for media files. 
     *
     * @param string $dir
     * @return void
     */
    public static function addSource(string $dir)
    {
        if (($dir = realpath($dir)) && is_dir($dir) && !in_array($dir, self::$sources)) {
            array_unshift(self::$sources, $dir);
        }
    }

    public static function locate(string $glob): ?string
    {
        $glob = static::prefixContext($glob);
        foreach (static::$sources as $source) {
            if ($result = array_filter(glob($source . $glob, GLOB_BRACE), '\is_file')) {
                return array_shift($result);
            }
        }
        return null;
    }

    public static function search(string $glob): array
    {
        $glob = static::prefixContext($glob);
        $result = [];
        foreach (static::$sources as $source) {
            $result = array_merge($result, array_filter(glob($source . $glob, GLOB_BRACE), '\is_file'));
        }
        return $result;
    }

    public static function get(string $path): ?File
    {
        return Cache::get(
            'media/' . md5($path),
            function () use ($path) {
                $path = static::prefixContext($path);
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                URLs::beginContext(new URL($path));
                $file = Dispatcher::firstValue("onGetMedia_$extension", [$path]) ??
                    Dispatcher::firstValue("onGetMedia", [$path]) ??
                    static::doGet($path);
                if ($file) {
                    Dispatcher::dispatchEvent("onFileReady_" . $file->extension(), [$file]);
                    Dispatcher::dispatchEvent("onFileReady", [$file]);
                }
                URLs::endContext();
                return $file;
            },
            Config::get('files.ttl')
        );
    }

    public static function glob(string $glob): array
    {
        $glob = static::prefixContext($glob);
        URLs::beginContext(new URL($glob));
        $files = array_map(
            function (string $path): string {
                return URLs::context()->directory() . basename($path);
            },
            static::search($glob)
        );
        $files = array_unique($files);
        asort($files);
        $files = array_map(
            function (string $path): File {
                return static::get($path);
            },
            $files
        );
        URLs::endContext();
        return $files;
    }

    public static function globToPaths(string $glob): array
    {
        $glob = static::prefixContext($glob);
        URLs::beginContext(new URL($glob));
        $files = array_map(
            function (string $path): string {
                return URLs::context()->directory() . basename($path);
            },
            static::search($glob)
        );
        $files = array_unique($files);
        asort($files);
        URLs::endContext();
        return $files;
    }

    public static function onGetMedia_js(string $path): ?File
    {
        // glob handler
        if (basename($path) == '*.js') {
            return new DeferredFile(
                basename(dirname($path)) . '.js',
                function (DeferredFile $file) use ($path) {
                    file_put_contents(
                        $file->path(),
                        CSS::scss(
                            implode(
                                PHP_EOL . PHP_EOL,
                                array_map(
                                    function (string $path): string {
                                        return static::get($path)->content();
                                    },
                                    static::globToPaths($path)
                                )
                            )
                        )
                    );
                },
                $path
            );
        }
        // single file handler
        if ($source = static::locate($path)) {
            return new DeferredFile(
                basename($path),
                function (DeferredFile $file) use ($source, $path) {
                    file_put_contents(
                        $file->path(),
                        JS::js(file_get_contents($source), $path)
                    );
                },
                md5_file($source)
            );
        }
        return null;
    }

    public static function onGetMedia_scss(string $path): ?File
    {
        return static::onGetMedia_css($path);
    }

    public static function onGetMedia_css(string $path): ?File
    {
        // glob handler
        if (basename($path) == '*.css') {
            return new DeferredFile(
                basename(dirname($path)) . '.css',
                function (DeferredFile $file) use ($path) {
                    file_put_contents(
                        $file->path(),
                        CSS::scss(
                            implode(
                                PHP_EOL,
                                array_map(
                                    function (string $path): string {
                                        return "@import \"$path\";";
                                    },
                                    static::globToPaths(preg_replace('/\.css$/', '.{scss,css}', $path))
                                )
                            )
                        )
                    );
                },
                $path
            );
        }
        // single file handler
        if ($source = static::locate(substr($path, 0, strlen($path) - 3) . '{css,scss}')) {
            return new DeferredFile(
                preg_replace('/\.scss$/', '.css', basename($path)),
                function (DeferredFile $file) use ($source, $path) {
                    switch (strtolower(pathinfo($source, PATHINFO_EXTENSION))) {
                        case 'scss':
                            file_put_contents(
                                $file->path(),
                                CSS::scss(file_get_contents($source), $path)
                            );
                            break;
                        default:
                            file_put_contents(
                                $file->path(),
                                CSS::css(file_get_contents($source))
                            );
                            break;
                    }
                },
                [$path, md5_file($source)]
            );
        }
        return null;
    }

    protected static function prefixContext(string $path)
    {
        if (substr($path, 0, 1) != '/') {
            $path = '/' . URLs::context()->route() . '/' . $path;
        }
        return $path;
    }

    protected static function doGet(string $path): ?File
    {
        if ($source = static::locate($path)) {
            return new DeferredFile(
                basename($path),
                function (DeferredFile $file) use ($source) {
                    copy($source, $file->path());
                },
                md5_file($source)
            );
        }
        return null;
    }

    public static function filePath(File $file): string
    {
        return Config::get('files.path') . '/' . static::idPath($file) . '/' . $file->filename();
    }

    public static function fileUrl(File $file): string
    {
        $filename = urlencode($file->filename());
        $filename = str_replace('+', '%20', $filename);
        if (Config::get('files.external')) {
            return Config::get('files.url') . '/' . static::idPath($file) . '/' . $filename;
        } else {
            return URLs::site() . Config::get('files.url') . '/' . static::idPath($file) . '/' . $filename;
        }
    }

    protected static function idPath(File $file): string
    {
        return implode('/', [
            substr($file->identifier(), 0, 1),
            substr($file->identifier(), 1, 2),
            substr($file->identifier(), 3)
        ]);
    }
}
