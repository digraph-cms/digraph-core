<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Media;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;

class MediaHelper extends AbstractHelper
{
    protected $mimes = [
        'css' => 'text/css'
    ];

    public function get($search)
    {
        $args = [];
        $argString = '';
        if ($search instanceof Url) {
            $args = $search['args'];
            $argString = $search->argString();
            $search = preg_replace('/\/$/', '', $search->pathString());
        }
        foreach (array_reverse($this->cms->config['media.paths']) as $path) {
            $path .= '/'.$search;
            if (is_file($path)) {
                return $this->prepare($path);
            }
        }
        return null;
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
        //set content
        if ($original != $content) {
            $out['content'] = $content;
        }
        return $out;
    }

    protected function prepare($file, $mime=null, $filename=null)
    {
        if (!$filename) {
            $filename = basename($file);
        }
        //set mime from extension list
        $ext = strtolower(preg_replace('/^.*\./', '', $filename));
        if (isset($this->mimes[$ext])) {
            $mime = $this->mimes[$ext];
        } else {
            $mime = mime_content_type($file);
        }
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
}