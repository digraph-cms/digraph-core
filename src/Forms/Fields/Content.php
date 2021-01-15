<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Checkbox;
use Formward\Fields\Container;

class Content extends Container
{
    protected $_extra = true;
    protected $_selectable = true;

    public function required($set = null, $clientSide = true)
    {
        return $this['text']->required($set, false);
    }

    public function extra($set = null)
    {
        if ($set !== null) {
            $this->_extra = $set;
        }
        //hide filter selector from UI (doesn't actually disable, not a security measure!)
        if ($this['extra']) {
            $this['extra']->hidden(!$this->_extra);
        }
        //return value
        return $this->_extra;
    }

    public function selectable($set = null)
    {
        if ($set !== null) {
            $this->_selectable = $set;
        }
        //hide filter selector from UI (doesn't actually disable, not a security measure!)
        if ($this->_selectable) {
            $this['filter']->removeClass('hidden');
        } else {
            $this['filter']->addClass('hidden');
        }
        //return value
        return $this->_selectable;
    }

    function default($default = null) {
        if (is_string($default)) {
            $default = [
                'text' => $default,
            ];
        }
        return parent::default($default);
    }

    public function value($value = null)
    {
        if (is_string($value)) {
            $value = [
                'text' => $value,
            ];
        }
        return parent::value($value);
    }

    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null)
    {
        $s = $cms->helper('strings');
        $f = $cms->helper('filters');
        parent::__construct($label, $name, $parent);
        $this['text'] = new ContentTextarea($s->string('forms.digraph_content.label_text'));
        $this['filter'] = new ContentFilter($s->string('forms.digraph_content.label_filter'), null, null, $cms);
        $this['filter']->addClass('FilterSelector');
        $this->addClass('DigraphContent');
        //find allowed extras and add field
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
            if ($cms->config['strings.forms.digraph_content.extras.' . $name]) {
                $label = $s->string('forms.digraph_content.extras.' . $name, ['tags' => $label]);
            }
            if ($cms->helper('permissions')->check('extra/' . $name, 'filter')) {
                $extras[$name] = new Checkbox($label);
                $extrasAllowed = true;
            }
        }
        if ($extrasAllowed) {
            $this['extra'] = $extras;
        }
    }
}
