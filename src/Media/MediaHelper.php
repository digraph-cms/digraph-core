<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Media;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;

class MediaHelper extends AbstractHelper
{
    protected $mimes = null;

    public function get($search)
    {
        //load from cache if possible
        if ($this->cms->config['media.get_cache_ttl']) {
            $cacheID = 'MediaHelper.get.'.md5($search);
            $cache = $this->cms->cache();
            if ($cache->hasItem($cacheID)) {
                return $cache->getItem($cacheID)->get();
            }
        }
        /* build result */
        $result = null;
        $args = [];
        $argString = '';
        if ($search instanceof Url) {
            $args = $search['args'];
            $argString = $search->argString();
            $search = preg_replace('/\/$/', '', $search->pathString());
        }
        //search in media paths
        foreach (array_reverse($this->cms->config['media.paths']) as $path) {
            $path .= '/'.$search;
            if (is_file($path)) {
                $result = $this->prepare($path);
                break;
            }
        }
        //save to cache and return
        if ($this->cms->config['media.get_cache_ttl']) {
            $citem = $cache->getItem($cacheID);
            $citem->expiresAfter($this->cms->config['media.get_cache_ttl']);
            $citem->set($result);
            $cache->save($citem);
        }
        return $result;
    }

    public function getContent($search)
    {
        if ($res = $this->get($search)) {
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
        $original = $content = file_get_contents($out['path']);
        //preprocess theme
        $content = preg_replace_callback(
            '/\\/\*{theme\:([^\}]+)\}\*\//',
            function ($matches) {
                $out = [];
                $name = $matches[1];
                foreach ($this->cms->helper('templates')->theme() as $theme) {
                    $out[] = "/*{include:_themes/$theme/$name.css}*/";
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
                if ($bundle = $this->cms->config['templates.cssbundles.'.$name]) {
                    $out = ['/* begin bundle: '.$name.' */'];
                    foreach ($bundle as $file) {
                        $out[] = '/*{include:'.$file.'}*/';
                    }
                    $out[] = '/* end bundle: '.$name.' */';
                    return implode(PHP_EOL, $out);
                }
                return '/* NOTICE: bundle '.$name.' not found */';
            },
            $content
        );
        //preprocess files
        $content = preg_replace_callback(
            '/\\/\*{include\:([^\}]+)\}\*\//',
            function ($matches) {
                $name = $matches[1];
                if (!($file = $this->cms->config['templates.css.'.$name])) {
                    $file = $name;
                }
                if ($content = $this->getContent($file)) {
                    $out = ['/* begin include: '.$name.' */'];
                    $out[] = $content;
                    $out[] = '/* end include: '.$name.' */';
                    return implode(PHP_EOL, $out);
                }
                return '/* NOTICE: file '.$name.' not found */';
            },
            $content
        );
        //run through template helper
        $content = $this->cms->helper('templates')->renderString($content, $this->fields());
        //set content
        if ($original != $content) {
            $out['content'] = $content;
        }
        return $out;
    }

    protected function prepare_application_javascript($out)
    {
        $original = $content = file_get_contents($out['path']);
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
                if ($bundle = $this->cms->config['templates.jsbundles.'.$name]) {
                    $out = ['/* begin bundle: '.$name.' */'];
                    foreach ($bundle as $file) {
                        $out[] = '/*{include:'.$file.'}*/';
                    }
                    $out[] = '/* end bundle: '.$name.' */';
                    return implode(PHP_EOL, $out);
                }
                return '/* NOTICE: bundle '.$name.' not found */';
            },
            $content
        );
        //preprocess files
        $content = preg_replace_callback(
            '/\\/\*{include\:([^\}]+)\}\*\//',
            function ($matches) {
                $name = $matches[1];
                if (!($file = $this->cms->config['templates.js.'.$name])) {
                    $file = $name;
                }
                if ($content = $this->getContent($file)) {
                    $out = ['/* begin include: '.$name.' */'];
                    $out[] = $content;
                    $out[] = '/* end include: '.$name.' */';
                    return implode(PHP_EOL, $out);
                }
                return '/* NOTICE: file '.$name.' not found */';
            },
            $content
        );
        //run through template helper
        $content = $this->cms->helper('templates')->renderString($content, $this->fields());
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
            foreach (@explode("\n", @file_get_contents(__DIR__.'/mime.types'))as $x) {
                if (isset($x[0]) && $x[0]!=='#' && preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c=count($out[1])) > 1) {
                    for ($i=1;$i<$c;$i++) {
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

    protected function prepare($file, $mime=null, $filename=null)
    {
        if (!$filename) {
            $filename = basename($file);
        }
        //set mime from extension list
        $mime = $this->mime($filename);
        //set up default output
        $out = [
            'path' => $file,
            'mime' => $mime,
            'filename' => basename($file)
        ];
        //check for handler function
        $fn = 'prepare_'.preg_replace('/[^a-z]/', '_', $mime);
        if (method_exists($this, $fn)) {
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
