<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\Fields\Container;
use Formward\FieldInterface;
use Digraph\CMS;
use Formward\Fields\Checkbox;

class ContentDefault extends Content
{
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
        $out['filter'] = 'default';
        return $out;
    }

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        parent::__construct($label, $name, $parent, $cms);
        unset($this['filter']);
        unset($this['extra']);
        $this['text']->label('');
    }
}
