<?php

namespace DigraphCMS\Media;

use DigraphCMS\Config;
use DigraphCMS\FS;
use DigraphCMS\URL\URLs;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use tubalmartin\CssMin\Minifier;

class CSS
{

    public static function scss(string $css, string $path = '/_source.scss'): string
    {
        // parse SCSS
        $css = static::parseSCSS($css, $path);
        // resolve URLs
        $css = static::resolveURLs($css);
        // return
        return $css;
    }

    public static function css(string $css): string
    {
        // resolve URLs
        $css = static::resolveURLs($css);
        // minify if configured
        if (Config::get('files.css.minify')) {
            $css = static::minify($css);
        }
        // return
        return $css;
    }

    public static function minify(string $css): string
    {
        $compressor = new Minifier();
        $compressor->keepSourceMapComment(Config::get('files.css.sourcemap'));
        $compressor->removeImportantComments(Config::get('files.css.keepimportantcomments'));
        return $compressor->run($css);
    }

    protected static function parseSCSS(string $scss, string $path): string
    {
        $compiler = new Compiler();
        // source mapping
        $smFile = null;
        if (Config::get('files.css.sourcemap')) {
            // pass info into temporary File to create file and get URL
            $smFile = new File('sourcemap.json', '', $scss);
            $smFile->write();
            // copy raw scss into a source file
            $path = preg_replace('/\.css$/', '.scss', $path);
            FS::mkdir(dirname(dirname($smFile->path()) . '/' . $path));
            file_put_contents(dirname($smFile->path()) . '/' . $path, $scss);
            // pass options into compiler
            $compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
            $basePath = realpath(dirname($smFile->path()));
            $basePath = str_replace('\\', '/', $basePath);
            $compiler->setSourceMapOptions([
                'sourceMapURL' => $smFile->url(),
                'sourceMapBasepath' => $basePath,
                'sourceRoot' => dirname($smFile->url())
            ]);
        }
        // set up import handler
        $compiler->setImportPaths([]);
        $compiler->addImportPath(function ($path) use ($smFile) {
            if ($source = Media::locate(preg_replace('/\.s?css$/', '.{scss,css}', $path))) {
                if ($smFile) {
                    $dest = pathinfo($smFile->path(), PATHINFO_DIRNAME) . '/' . $path;
                    FS::mkdir(dirname($dest));
                    copy($source, $dest);
                    file_put_contents($dest, PHP_EOL . '/* SOURCE OF ' . $path . ' */', FILE_APPEND);
                    return $dest;
                }
                return $source;
            }
            return null;
        });
        // minification
        if (Config::get('files.css.minify')) {
            $compiler->setOutputStyle(OutputStyle::COMPRESSED);
        }
        // compile
        $result = $compiler->compileString(
            $scss,
            $smFile ? dirname($smFile->path()) . '/' . $path : null
        );
        // save source map if necessary
        if ($smFile) {
            file_put_contents($smFile->path(), $result->getSourceMap());
        }
        // compile and return
        return $result->getCss();
    }

    protected static function resolveURLs(string $css): string
    {
        return preg_replace_callback(
            "/url\(([\"']?)([^\"'\)]+)([\"']?)\)/",
            function ($matches) {
                // quotes must match or it's malformed
                if ($matches[1] != $matches[3]) {
                    return $matches[0];
                }
                //skip data urls
                if (substr($matches[2], 0, 5) == 'data:') {
                    return $matches[0];
                }
                //get url from matches
                $url = $matches[2];
                if ($file = Media::get($url)) {
                    return 'url("' . $file->url() . '")';
                } else {
                    return $matches[0];
                }
            },
            $css
        );
    }
}
