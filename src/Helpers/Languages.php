<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;
use Flatrr\Config\Config;

class Languages extends AbstractHelper
{
    public function initialize()
    {
        $langs = $this->cms->config['lang.load'];
        $langs = array_reverse($langs);
        $langs = array_unique($langs);
        foreach ($langs as $name) {
            $this->loadLang($name);
        }
    }

    public function loadLang($name)
    {
        foreach ($this->cms->config['lang.paths'] as $path) {
            if (is_dir($path)) {
                $config = $path.'/'.$name.'/config.yaml';
                $strings = $path.'/'.$name.'/strings.yaml';
                if (is_file($config)) {
                    $this->cms->config->readFile($config);
                }
                if (is_file($strings)) {
                    $this->cms->config->readFile($strings, 'lang.strings');
                }
            }
        }
    }

    public function string($name, array $args=[]) : string
    {
        if ($string = $this->cms->config['lang.strings.'.$name]) {
            foreach ($args as $key => $value) {
                $string = str_replace('!'.$key, $value, $string);
            }
            return $string;
        }
        return '[lang.strings.'.$name.']';
    }
}
