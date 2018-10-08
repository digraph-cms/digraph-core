<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\Fields\Container;
use Formward\FieldInterface;
use Digraph\CMS;
use Formward\Fields\Checkbox;

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

    public function default($default = null)
    {
        if (!is_array($default)) {
            return [];
        }
        return parent::default($default);
    }

    public function value($value = null)
    {
        if (!is_array($default)) {
            return [];
        }
        return parent::value($value);
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
        $this['filter']->required(true);
        //options for enabling/disabling digraph tag filters
        $this['links'] = new Checkbox('Enable Digraph link tags');
        $this['embeds'] = new Checkbox('Enable Digraph embed tags');
        $this['templates'] = new Checkbox('Enable Digraph template tags');
    }
}
