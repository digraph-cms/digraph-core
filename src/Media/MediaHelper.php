<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Media;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;
use MatthiasMullie\Minify;
use Symfony\Component\Filesystem\Filesystem;

class MediaHelper extends AbstractHelper
{
    protected $mimes;

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
        $args = [];
        $argString = '';
        if ($search instanceof Url) {
            $args = $search['args'];
            $argString = $search->argString();
            $search = preg_replace('/\/$/', '', $search->pathString());
        }
        //load from cache if possible
        if ($this->cms->config['media.get_cache_ttl']) {
            $cacheID = 'MediaHelper.get.' . md5(serialize([$search, $raw]));
            $cache = $this->cms->cache();
            if ($cache->hasItem($cacheID)) {
                return $cache->getItem($cacheID)->get();
            }
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
            $result = $this->prepare($result, null, null, $dfiles, $raw);
            $result['search'] = $search;
        }
        //save to cache
        if ($ttl = $this->cms->config['media.get_cache_ttl']) {
            $citem = $cache->getItem($cacheID);
            $citem->expiresAfter($ttl);
            $citem->set($result);
            $cache->save($citem);
        }
        //save static cache if applicable
        if ($this->canBeStaticCached($result)) {
            $path = $this->cms->config['paths.web'] . '/' . $result['search'];
            $fs = new Filesystem;
            if (isset($result['content'])) {
                $fs->dumpFile($path, $result['content']);
            } else {
                $fs->dumpFile($path, file_get_contents($result['path']));
            }
        }
        //return
        return $result;
    }

    protected function canBeStaticCached($result)
    {
        if (!$this->cms->config['media.static_cache']) {
            return false;
        }
        if (empty($result['search'])) {
            return false;
        }
        return true;
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
