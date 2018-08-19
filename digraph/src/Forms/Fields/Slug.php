<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Forms\Fields;

use Digraph\Forms\Fields\Input;
use Digraph\Forms\FieldInterface;

class Slug extends Input
{
    //The characters allowed in addition to alphanumerics and slashes
    const CHARS = '$-_.+!*\'(),';

    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
        //add a validator to trim slugs and ensure they're valid
        $this->addValidatorFunction(
            'validurl',
            function (&$field) {
                $value = $field->value();
                if (strpos('//', $value) !== false) {
                    return 'Slug can\'t have more than one slash in a row';
                }
                if (preg_match('/[^a-z0-9\/'.preg_quote(static::CHARS).']/i', $value)) {
                    return 'Slug contains an invalid character. Allowed characters are alphanumerics, forward slashes, and <code>'.static::CHARS.'</code>';
                }
                return true;
            }
        );
    }

    //trims off leading/trailing slashes
    public function value($value = null)
    {
        parent::value($value);
        $value = parent::value();
        $value = trim($value, "\/ \t\n\r\0\x0B");
        return $value;
    }
}
