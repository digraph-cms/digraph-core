<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\Fields\Select;
use Formward\FieldInterface;
use Digraph\CMS;

class ContentFilter extends Select
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        parent::__construct($label, $name, $parent);
        //load filters from cms
        $options = [];
        foreach ($cms->config['filters.presets'] as $key => $value) {
            //leading underscores mean it's a system filter
            if (substr($key, 0, 1) == '_') {
                continue;
            }
            //basic permissions
            $permission = $cms->helper('permissions')->check('preset/'.$key, 'filter');
            //unsafe permissions
            if (substr($key, -5) == '-safe') {
                $permission = $permission || $cms->helper('permissions')->check('safe', 'filter');
            }
            //unsafe permissions
            if (substr($key, -7) == '-unsafe') {
                $permission = $permission && $cms->helper('permissions')->check('unsafe', 'filter');
            }
            //add to options if permission is allowed
            if ($permission) {
                if (!($name = $cms->config['filters.labels.'.$key])) {
                    $name = $key;
                }
                $options[$key] = $name;
            }
        }
        $this->options($options);
        $this->required(true);
        if (count($options) == 1) {
            $this->addClass('hidden');
        }
    }
}
