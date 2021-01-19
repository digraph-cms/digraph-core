<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Container;
use Formward\Fields\Input;

class AbstractAutocomplete extends Container
{
    protected $srcArgs = [];

    public function srcArg(string $key, string $value = null)
    {
        if ($value !== null) {
            $this->srcArgs[$key] = $value;
            $this->attr('data-srcargs', json_encode($this->srcArgs));
        }
        return @$this->srcArgs[$key];
    }

    protected function validateValue(string $value): bool
    {
        return true;
    }

    public function required($set = null, $clientSide = true)
    {
        if ($set !== null) {
            $this['actual']->required($set, false);
            if ($set) {
                $this->addClass('required');
            } else {
                $this->removeClass('required');
            }
        }
        return $this['actual']->required();
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
        $this->cms = $cms;
        $this->addClass('DigraphAutocomplete');
        $this->addClass('TransparentContainer');
        $this->attr('data-autocomplete', static::SOURCE);
        $this['user'] = new Input($label);
        $this['user']->addClass('AutocompleteUser');
        $this['actual'] = new Input($label);
        $this['actual']->addClass('AutocompleteActual');
        $this->addValidatorFunction(
            'valid-autocomplete-value',
            function ($field) {
                if (!$field->value()) {
                    return true;
                }
                if (!$this->validateValue($field->value())) {
                    return 'Autocomplete value failed validation. Please try again.';
                }
                return true;
            }
        );
    }

    /**
     * HTML tag content is a list of all the elements in this container
     */
    protected function htmlContent(): ?string
    {
        $out = $this->label() ? '<label>' . $this->label() . '</label>' : '';
        $out .= '<noscript><div class="notification notification-error">Javascript is required to use this form field</div></noscript>';
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
