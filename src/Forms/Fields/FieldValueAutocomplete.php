<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;

class FieldValueAutocomplete extends AbstractAutocomplete
{
    const SOURCE = 'fieldvalue';

    public function __construct(string $label, string $name = null, FieldInterface $parent = null, $cms = null, array $types = [], array $fields = [], bool $allowAdding = false)
    {
        parent::__construct($label, $name, $parent);
        $this->cms = $cms;
        // set up session token for this particular field's config
        $config = [
            'types' => $types,
            'fields' => $fields,
            'allowAdding' => $allowAdding
        ];
        $this->configToken = md5(static::class . serialize($config));
        $session = $this->cms->helper('session');
        $session->set($this->configToken, $config);
        $this->attr('data-autocomplete-token', $this->configToken);
        // $this->addValidatorFunction(
        //     'validtimestamp',
        //     function ($field) {
        //         if (@$field->value() != intval(@$field->value())) {
        //             return 'Input must be a valid UNIX timestamp. This field is not easily usable without Javascript enabled.';
        //         }
        //         return true;
        //     }
        // );
    }
}
