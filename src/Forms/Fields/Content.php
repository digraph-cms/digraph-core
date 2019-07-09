<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\Fields\Container;
use Formward\FieldInterface;
use Digraph\CMS;
use Formward\Fields\Checkbox;

class Content extends Container
{
    protected $_extra = true;
    protected $_selectable = false;

    public function extra($set=null)
    {
        if ($set !== null) {
            $this->_extra = $set;
        }
        return $this->_extra;
    }

    public function selectable($set=null)
    {
        if ($set !== null) {
            $this->_selectable = $set;
        }
        return $this->_selectable;
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
        $s = $cms->helper('strings');
        $f = $cms->helper('filters');
        parent::__construct($label, $name, $parent);
        $this['text'] = new ContentTextarea($s->string('forms.digraph_content.label_text'));
        $this['filter'] = new ContentFilter($s->string('forms.digraph_content.label_filter'), null, null, $cms);
        //hide selector from UI (doesn't actually disable, not a security measure!)
        if (!$this->selectable) {
            $this['filter']->addClass('hidden');
        }
        //options for enabling/disabling extra filters
        $extrasAllowed = false;
        $extras = new Container($s->string('forms.digraph_content.label_extras'));
        foreach ($cms->config['filters.extras'] as $name => $enabled) {
            if (!$enabled) {
                continue;
            }
            if (!($filter = $f->filter($name))) {
                continue;
            }
            $label = $filter->tagsProvidedString();
            if ($cms->config['strings.forms.digraph_content.extras.'.$name]) {
                $label = $s->string('forms.digraph_content.extras.'.$name, ['tags'=>$label]);
            }
            if ($cms->helper('permissions')->check('extra/'.$name, 'filter')) {
                $extras[$name] = new Checkbox($label);
                $extrasAllowed = true;
            }
        }
        if ($extrasAllowed && $this->extra) {
            $this['extra'] = $extras;
        }
    }
}
