<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
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

    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null)
    {
        parent::__construct('', $name, $parent);
        $this->addClass('DigraphAutocomplete');
        $this->addClass('TransparentContainer');
        $this->attr('data-autocomplete', static::SOURCE);
        $this['user'] = new Input($label);
        $this['user']->addClass('AutocompleteUser');
        $this['actual'] = new Input('Actual input');
        $this['actual']->addClass('AutocompleteActual');
    }

    /**
     * HTML tag content is a list of all the elements in this container
     */
    protected function htmlContent(): ?string
    {
        $out = $this->label() ? '<label>' . $this->label() . '</label>' : '';
        $out .= PHP_EOL . implode(PHP_EOL, array_map(
            function ($item) {
                return $this->containerItemHtml($item);
            },
            $this->get()
        )) . PHP_EOL;
        $out .= $this->htmlTips();
        return $out;
    }
}
