<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Container;
use Formward\Fields\Input;

class AbstractAutocomplete extends Container
{
    public function required($set = null)
    {
        return $this['actual']->required($set);
    }

    public function value($set = null)
    {
        return $this['actual']->value($set);
    }

    function default($set = null) {
        return $this['actual']->default($set);
    }

    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS &$cms = null)
    {
        parent::__construct('', $name, $parent);
        $this->addClass('DigraphAutocomplete');
        $this->addClass('TransparentContainer');
        $this->attr('data-autocomplete', static::SOURCE);
        $this['actual'] = new Input('Actual input');
        $this['actual']->addClass('AutocompleteActual');
        $this['user'] = new Input($label);
        $this['user']->addClass('AutocompleteUser');
        $this['userindex'] = new Input('User input selected item');
        $this['userindex']->addClass('AutocompleteUserIndex');
    }
}
