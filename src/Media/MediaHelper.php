<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Media;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;
use MatthiasMullie\Minify;

class MediaHelper extends AbstractHelper
{
    protected $mimes;

    public function create($filename, $content, $identifier = null, int $ttl = null): Asset
    {
        // load ttl from config if not specified
        $ttl = $ttl ?? $this->cms->config['media.assets.ttl'];
        // generate an identifier if none is specified
        // note that this loses the potential performance gains if
        // using a callback for content, as it forces the callback to run
        if ($identifier === null) {
            if (is_callable($content)) {
                $content = $content();
            }
            $identifier = md5($content);
        }
        // determine if we need to write content to asset file
        $path = $this->assetPath($filename, $identifier);
        $mtime = @filemtime($path . '.lastwrite') ?? filemtime($path);
        if (!is_file($path) || time() > $mtime + $ttl) {
            $this->writeAsset($this->cms->config['paths.assets'] . '/' . $path, $content);
        }
        $url = $this->cms->config['media.assets.url'] . $path;
        return new Asset([
            'filename' => $filename,
            'url' => $url,
            'path' => $this->cms->config['paths.assets'] . '/' . $path,
            'mime' => $this->mime($filename),
        ]);
    }

    protected function writeAsset($path, $content)
    {
        if (is_callable($content)) {
            // use a callback to write content into a file
            $content($path);
        } else {
            $this->cms->helper('filesystem')->put(
                $content,
                $path,
                true
            );
        }
        touch($path . '.lastwrite');
    }

    protected function assetPath($filename, $identifier = null): string
    {
        $path = md5(serialize($identifier));
        $path = preg_replace('/^(..)(..)(.+)$/', '$1/$2/$3/', $path);
        $path .= $filename;
        return $path;
    }

    /**
     * Get all the paths from CMS config, with 'site' swapped to the end, and
     * the entire thing reversed. This way 'site' is highest priority, and
     * 'core' is lowest priority.
     *
     * @return array
     */
    protected function paths()
    {
        $paths = $this->cms->config['media.paths'];
        if ($site = @$paths['site']) {
            unset($paths['site']);
            $paths['site'] = $site;
        }
        return array_reverse($paths);
    }

    public function importPaths($search = null)
    {
        if ($search !== null) {
            $search = '/' . $search;
        }
        $searches = [];
        foreach ($this->paths() as $path) {
            $searches[] = $path . $search;
        }
        foreach (array_reverse($this->cms->helper('templates')->theme()) as $theme) {
            foreach (array_reverse($this->cms->config['media.paths']) as $path) {
                $searches[] = $path . '/_themes/' . $theme . $search;
            }
        }
        foreach (array_reverse($this->cms->config['media.paths']) as $path) {
            $searches[] = $path . '/_digraph' . $search;
        }
        return $searches;
    }

    public function get($search, $raw = false)
    {
        //sort out URL
        $result = null;
        if ($search instanceof Url) {
            $search = preg_replace('/\/$/', '', $search->pathString());
        }
        //search in media paths and theme paths
        $dfiles = [];
        $ext = preg_replace('/^.+\./', '', $search);
        $searches = $this->importPaths($search);
        foreach ($searches as $key => $path) {
            if (!$result && is_file($path)) {
                $result = $path;
            }
            if (is_dir($path . '.d')) {
                foreach (glob($path . '.d/*.' . $ext) as $f) {
                    $dfiles[basename($f) . $key] = $f;
                }
            }
        }
        //prepare
        if ($result) {
            ksort($dfiles);
            $result = $this->create(
                basename($search),
                function ($dest) use ($result, $dfiles, $raw) {
                    $result = $this->prepare($result, null, null, $dfiles, $raw);
                    if (isset($result['content'])) {
                        $this->cms->helper('filesystem')->put(
                            $result['content'],
                            $dest,
                            true
                        );
                    } else {
                        $this->cms->helper('filesystem')->copy(
                            $result['path'],
                            $dest,
                            true
                        );
                    }
                },
                [$result, array_map('\md5_file', $dfiles), $raw]
            );
            $result['search'] = $search;
            return $result;
        }
        //return null by default
        return null;
    }

    public function getContent($search, $raw = false)
    {
        if ($res = $this->get($search, $raw)) {
            if (isset($res['content'])) {
                return $res['content'];
            } else {
                return file_get_contents($res['path']);
            }
        }
        return null;
    }

    protected function prepare_text_css($out)
    {
        $original = file_get_contents($out['path']);
        $content = @$out['content'] ? $out['content'] : $original;
        //preprocess imports
        while (preg_match('/@import "(.+)"( (.+))?;/', $content)) {
            $content = preg_replace_callback(
                '/@import "(.+)"( (.+))?;/',
                function ($matches) {
                    $file = $matches[1];
                    $media = @trim($matches[3]);
                    $content = $this->getContent($file, true);
                    if ($content !== null) {
                        if ($media) {
                            $content = "@media $media {" . PHP_EOL . $content . PHP_EOL . "}";
                        }
                        $content = '/* import "' . $file . '" ' . @$media . ' */' . PHP_EOL . $content . PHP_EOL;
                        return $content;
                    } else {
                        return '/* import couldn\'t find ' . $file . ' */';
                    }
                },
                $content
            );
        }
        //run through template helper (that means we can do twig inside css!)
        $content = $this->cms->helper('templates')->renderString($content, $this->fields());
        //run through css crush
        if ($this->cms->config['media.css.crush-enabled']) {
            $options = $this->cms->config['media.css.crush-options'];
            if ($this->cms->config['media.css.minify']) {
                $options['minify'] = true;
            }
            $content = csscrush_string(
                $content,
                $options
            );
        }
        //rewrite asset URLs
        $content = preg_replace_callback(
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
                $base = $this->cms->config['url.base'];
                if (substr($url, 0, strlen($base)) == $base) {
                    $url = substr($url, strlen($base));
                }
                if ($asset = $this->get($url)) {
                    return 'url(' . $asset['url'] . ')';
                } else {
                    return $matches[0];
                }
            },
            $content
        );
        //minify if enabled
        if ($this->cms->config['media.css.minify']) {
            $minifier = new Minify\CSS($content);
            $content = $minifier->minify();
        }
        //set content
        if ($original != $content) {
            $out['content'] = $content;
        }
        return $out;
    }

    protected function prepare_application_javascript($out)
    {
        $original = file_get_contents($out['path']);
        $content = @$out['content'] ? $out['content'] : $original;
        //preprocess theme
        $content = preg_replace_callback(
            '/\\/\*{theme\:([^\}]+)\}\*\//',
            function ($matches) {
                $out = [];
                $name = $matches[1];
                foreach ($this->cms->helper('templates')->theme() as $theme) {
                    $out[] = "/*{include:_themes/$theme/$name.js}*/";
                }
                return implode(PHP_EOL, $out);
            },
            $content
        );
        //preprocess bundles
        $content = preg_replace_callback(
            '/\\/\*{bundle\:([^\}]+)\}\*\//',
            function ($matches) {
                $name = $matches[1];
                if ($bundle = $this->cms->config['templates.jsbundles.' . $name]) {
                    $out = ['/* begin bundle: ' . $name . ' */'];
                    foreach ($bundle as $file) {
                        $out[] = '/*{include:' . $file . '}*/';
                    }
                    $out[] = '/* end bundle: ' . $name . ' */';
                    return implode(PHP_EOL, $out);
                }
                return '/* NOTICE: bundle ' . $name . ' not found */';
            },
            $content
        );
        //preprocess files
        $content = preg_replace_callback(
            '/\\/\*{include\:([^\}]+)\}\*\//',
            function ($matches) {
                $name = $matches[1];
                if (!($file = $this->cms->config['templates.js.' . $name])) {
                    $file = $name;
                }
                if ($content = $this->getContent($file)) {
                    $out = ['/* begin include: ' . $name . ' */'];
                    $out[] = $content;
                    $out[] = '/* end include: ' . $name . ' */';
                    return implode(PHP_EOL, $out);
                }
                return '/* NOTICE: file ' . $name . ' not found */';
            },
            $content
        );
        //run through template helper
        $content = $this->cms->helper('templates')->renderString($content, $this->fields());
        //minify if enabled
        if ($this->cms->config['media.js.minify']) {
            $minifier = new Minify\JS($content);
            $content = $minifier->minify();
        }
        //set content
        if ($original != $content) {
            $out['content'] = $content;
        }
        return $out;
    }

    public function mime(string $filename)
    {
        //build mime list from apache mime.types (included in repo)
        if (!$this->mimes) {
            foreach (@explode("\n", @file_get_contents(__DIR__ . '/mime.types')) as $x) {
                if (isset($x[0]) && $x[0] !== '#' && preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c = count($out[1])) > 1) {
                    for ($i = 1; $i < $c; $i++) {
                        $this->mimes[$out[1][$i]] = $out[1][0];
                    }
                }
            }
        }
        //set mime from extension list
        $ext = strtolower(preg_replace('/^.*\./', '', $filename));
        if (isset($this->mimes[$ext])) {
            return $this->mimes[$ext];
        }
        return mime_content_type($filename);
    }

    protected function prepare($file, $mime = null, $filename = null, $dfiles = [], $raw = false)
    {
        if (!$filename) {
            $filename = basename($file);
        }
        //set mime from extension list
        if (!$mime) {
            $mime = $this->mime($filename);
        }
        //set up default output
        $out = [
            'path' => $file,
            'mime' => $mime,
            'filename' => basename($file),
        ];
        //if there are dfiles, load it all together
        if ($dfiles) {
            $content = [file_get_contents($file)];
            foreach ($dfiles as $df) {
                $content[] = '/* ' . $out['filename'] . '.d/' . basename($df) . ' */';
                $content[] = file_get_contents($df);
            }
            $out['content'] = implode(PHP_EOL, $content);
        }
        //check for handler function
        $fn = 'prepare_' . preg_replace('/[^a-z]/', '_', $mime);
        if (!$raw && method_exists($this, $fn)) {
            $out = $this->$fn($out);
        }
        //return output
        return $out;
    }

    protected function fields()
    {
        return [];
    }
}
