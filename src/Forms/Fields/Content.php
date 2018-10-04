<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\Fields\Container;
use Formward\FieldInterface;
use Digraph\CMS;

class Content extends Container
{
    protected $cms;
    //The characters allowed in addition to alphanumerics and slashes
    const CHARS = '$-_.+!*\'(),';

    public function &cms(CMS &$set = null) : CMS
    {
        if ($set) {
            $this->cms = $set;
        }
        return $this->cms;
    }

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this['text'] = new ContentTextarea('Text');
        $this['filter'] = new ContentFilter('Filter');
        //load filters from cms
        $this->cms = $cms;
        foreach ($this->cms->config['filters.presets'] as $key => $value) {
            if (!($name = $this->cms->config['filters.names.'.$key])) {
                $name = $key;
            }
            $options[$key] = $name;
        }
        $this['filter']->options($options);
    }
}
