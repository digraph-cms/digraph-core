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
        if (is_string($default)) {
            $default = [
                'text' => $default
            ];
        }
        return parent::default($default);
    }

    public function value($value = null)
    {
        if (is_string($value)) {
            $value = [
                'text' => $value
            ];
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
        $options = [];
        foreach ($this->cms->config['filters.presets'] as $key => $value) {
            if ($this->cms->helper('permissions')->check('preset/'.$key, 'filters')) {
                if (!($name = $this->cms->config['filters.names.'.$key])) {
                    $name = $key;
                }
                $options[$key] = $name;
            }
        }
        $this['filter']->options($options);
        $this['filter']->required(true);
        if (count($options) == 1) {
            $this['filter']->addClass('hidden');
        }
        //options for enabling/disabling extra filters
        $extrasAllowed = false;
        $extras = new Container('Extra filters');
        foreach ($this->cms->config['filters.extras'] as $name => $label) {
            if ($this->cms->helper('permissions')->check('extra/'.$name, 'filters')) {
                $extras[$name] = new Checkbox($label);
                $extrasAllowed = true;
            }
        }
        if ($extrasAllowed) {
            $this['extra'] = $extras;
        }
    }
}
