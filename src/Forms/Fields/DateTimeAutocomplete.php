<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;

class DateTimeAutocomplete extends AbstractAutocomplete
{
    const SOURCE = 'datetime';

    public function __construct(string $label, string $name = null, FieldInterface $parent = null, $cms = null)
    {
        parent::__construct($label, $name, $parent);
        $this->cms = $cms;
        $this->addTip('You can enter exact date/time strings in a variety of formats, or fuzzy relative terms such as "now" or "2 hours"', 'format');
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
