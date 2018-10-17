<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

use Digraph\Helpers\AbstractHelper;

class FilterHelper extends AbstractHelper
{
    protected $filters = [];
    protected $context = null;

    public function filterContentField(array $content, string $context) : string
    {
        $this->context($context);
        $text = $this->filterPreset(@$content['text'], @$content['filter']);
        if (@$content['extra']) {
            foreach ($content['extra'] as $name => $value) {
                if ($value) {
                    $text = $this->filter($name)->filter($text);
                }
            }
        }
        return $text;
    }

    public function context(string $context = null) : ?string
    {
        if ($context !== null) {
            $this->context = $context;
        }
        return $this->context;
    }

    public function filter(string $name, FilterInterface &$set = null) : ?FilterInterface
    {
        if (!isset($this->filters[$name])) {
            if (isset($this->cms->config['filters.classes.'.$name])) {
                $class = $this->cms->config['filters.classes.'.$name];
                $this->cms->log('Instantiating filter '.$name.': '.$class);
                $this->filters[$name] = new $class($this->cms);
            }
        }
        if (!isset($this->filters[$name])) {
            return null;
        }
        $this->filters[$name]->context($this->context);
        return $this->filters[$name];
    }

    public function links(string $text) : string
    {
        return $this->filter('digraph_links')->filter($text);
    }

    public function embeds(string $text) : string
    {
        return $this->filter('digraph_embeds')->filter($text);
    }

    public function templates(string $text) : string
    {
        return $this->filter('digraph_templates')->filter($text);
    }

    public function filterPreset(?string $text, string $name = null) : string
    {
        if ($text === null) {
            $text = '';
        }
        if (!isset($this->cms->config['filters.presets.'.$name])) {
            $name = 'default';
        }
        $filters = $this->cms->config['filters.presets.'.$name];
        foreach ($filters as $args) {
            $mode = array_shift($args);
            $with = array_shift($args);
            switch ($mode) {
                /* Use a prese to modify text (warning, this is recursive) */
                case 'preset':
                    $text = $this->filterPreset($text, $with);
                    break;
                /* Use a Filter class from filter() to modify text */
                case 'class':
                    if (!($f = $this->filter($with))) {
                        throw new \Exception("Unknown filter class \"$with\"");
                    }
                    $text = $f->filter($text, $args);
                    break;
                /* Default is to throw an exception since filter problems are a potential security hole */
                default:
                    throw new \Exception("Unknown filter rule mode \"$mode\"");
                    break;
            }
        }
        return $text;
    }
}
