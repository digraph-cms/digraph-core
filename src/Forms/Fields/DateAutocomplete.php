<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;

class DateAutocomplete extends AbstractAutocomplete
{
    const SOURCE = 'date';

    public function __construct(string $label, string $name = null, FieldInterface $parent = null, $cms = null)
    {
        parent::__construct($label, $name, $parent);
        $this->cms = $cms;
        $this->addTip('You can enter exact date strings in a variety of formats, or fuzzy relative terms such as "now" or "1 week"', 'format');
        $this->addValidatorFunction(
            'validtimestamp',
            function ($field) {
                if (preg_match('/[^0-9]/', @$field['actual']->value())) {
                    return 'Input must be a valid UNIX timestamp.<noscript><br>This field is not usable without Javascript enabled.</noscript>';
                }
                return true;
            }
        );
    }
}
