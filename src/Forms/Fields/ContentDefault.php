<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\Fields\Container;
use Formward\FieldInterface;
use Digraph\CMS;
use Formward\Fields\Checkbox;

class ContentDefault extends Content
{
    protected $cms;
    protected $filter = null;
    protected $extra = null;

    public function extra($set=null)
    {
        if ($set !== null) {
            $this->extra = $set;
            $f = $this->cms->helper('filters');
            $s = $this->cms->helper('strings');
            $labels = array_filter(array_map(
                function ($e) use ($f,$s) {
                    if ($filter = $f->filter($e)) {
                        $label = $filter->tagsProvidedString();
                        return $label = $s->string('forms.digraph_content.extras.'.$e, ['tags'=>$label]);
                    }
                    return false;
                },
                array_keys(array_filter($set))
            ));
            if ($labels) {
                $this->addTip(implode('<br>', $labels), 'extra_filters');
            } else {
                $this->removeTip('extra_filters');
            }
        }
        if ($this->extra === null) {
            $this->extra(['bbcode_basic'=>true]);
        }
        return $this->extra;
    }

    public function filter($set=null)
    {
        if ($set !== null) {
            if ($label = $this->cms->config['filters.labels.'.$set]) {
                $this->addTip('Parsed as: '.$label, 'content_filter');
            } else {
                $this->removeTip('content_filter');
            }
            $this->filter = $set;
        }
        if ($this->filter === null) {
            $this->filter('default');
        }
        return $this->filter;
    }

    public function default($default = null)
    {
        if (is_string($default)) {
            $default = [
                'text' => $default
            ];
        }
        $out = parent::default($default);
        $out['filter'] = 'default';
        return $out;
    }

    public function value($value = null)
    {
        if (is_string($value)) {
            $value = [
                'text' => $value
            ];
        }
        $out = parent::value($value);
        $out['filter'] = $this->filter();
        $out['extra'] = $this->extra();
        return $out;
    }

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        parent::__construct($label, $name, $parent, $cms);
        $this->cms = $cms;
        $this['filter']->addClass('hidden');
        unset($this['extra']);
        $this['text']->label('');
        $this->filter();
        $this->extra();
    }
}
