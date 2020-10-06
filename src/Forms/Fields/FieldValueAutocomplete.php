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
            'allowAdding' => $allowAdding,
        ];
        $this->configToken = md5(static::class . serialize($config));
        $session = $this->cms->helper('session');
        $session->set($this->configToken, $config);
        $this->attr('data-autocomplete-token', $this->configToken);
        // validator to prevent adding new values without permission
        if (!$allowAdding) {
            $this->addValidatorFunction(
                'validfieldvalue',
                function ($field) use ($types, $fields) {
                    $search = $this->cms->factory()->search();
                    $where = [];
                    if ($types) {
                        $where[] = '${dso.type} in ("' . implode('","', $types) . '")';
                    }
                    $fieldSearch = [];
                    foreach ($fields as $f) {
                        $fieldSearch[] = '${' . $f . '} = :q';
                    }
                    $where[] = '(' . implode(' OR ', $fieldSearch) . ')';
                    $where = implode(' AND ', $where);
                    $search->where($where);
                    $search->limit(1);
                    if (!$search->execute(['q' => $field['user']->value()])) {
                        return 'Input must be a valid existing value.<noscript><br>This field is not easily usable without Javascript enabled.</noscript>';
                    }
                    return true;
                }
            );
        }
    }
}
