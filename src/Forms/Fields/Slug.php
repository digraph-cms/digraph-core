<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;
use Formward\Fields\Checkbox;
use Formward\Fields\Container;
use Formward\Fields\Input;

class Slug extends Container
{
    //The characters allowed in addition to alphanumerics and slashes
    const CHARS = '$-_.+!*\'(),';

    protected $noun;

    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
        $this['enable'] = new Checkbox('Enable custom URL');
        $this['parent_slug'] = new Checkbox('[parent]/');
        $this['parent_slug']->default(true);
        $this['my_slug'] = new Input('');
        //add a validator to trim slugs and ensure they're valid
        $this->addValidatorFunction(
            'validurl',
            function (&$field) {
                $value = $field->dsoValue();
                if (!$value) {
                    return true;
                }
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

    public function dsoValue()
    {
        if (!$this['enable']->value()) {
            return null;
        }
        $slug = $this['parent_slug']->value()?$this['parent_slug']->label():'';
        $slug .= $this['my_slug']->value();
        $slug = trim($slug, "\/ \t\n\r\0\x0B");
        return $slug;
    }

    public function dsoNoun(&$noun)
    {
        $this->noun = $noun;
        $url = $noun->parentUrl();
        $this['parent_slug']->label($url['noun'].'/');
        if ($this['parent_slug']->label() == 'home/' || $this['parent_slug']->label() == '/') {
            $this['parent_slug']->label('');
            $this['parent_slug']->addClass('hidden');
        }
    }

    public function default($value = null)
    {
        if (is_string($value)) {
            if ($value) {
                $this['enable']->value(true);
                if ($this['parent_slug']->label() && strpos($value, $this['parent_slug']->label()) === 0) {
                    $this['parent_slug']->default(true);
                    $this['my_slug']->default(substr($value, strlen($this['parent_slug']->label())));
                } else {
                    $this['parent_slug']->default(false);
                    $this['my_slug']->default($value);
                }
            }
        } else {
            parent::default($value);
        }
        return parent::default();
    }

    //trims off leading/trailing slashes
    public function value($value = null)
    {
        if (is_string($value)) {
        } else {
            parent::value($value);
        }
        $this['my_slug']->value(
            trim($this['my_slug']->value(), "\/ \t\n\r\0\x0B")
        );
        return parent::value();
    }
}
