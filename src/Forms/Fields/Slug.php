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
        $this['parent_slug'] = new Checkbox('[parent]/');
        $this['parent_slug']->default(true);
        $this['my_slug'] = new Input('');
        $this['my_slug']->addClass('CustomSlug');
        //add a validator to trim slugs and ensure they're valid
        $this->addValidatorFunction(
            'validurl',
            function (&$field) {
                $value = $field->dsoValue();
                if (!$value) {
                    return true;
                }
                if (substr($value, 0, 1) == '_') {
                    return $this->cms->helper('strings')->string('forms.slug.error.underscore');
                }
                if (strpos('//', $value) !== false) {
                    return $this->cms->helper('strings')->string('forms.slug.error.slashes');
                }
                if (preg_match('/[^a-z0-9\/'.preg_quote(static::CHARS).']/i', $value)) {
                    return $this->cms->helper('strings')->string('forms.slug.error.character', [static::CHARS]);
                }
                return true;
            }
        );
    }

    public function dsoValue()
    {
        if (!$this['my_slug']->value()) {
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
        //set up my slug
        $this['my_slug']->default($noun['dso.id']);
        //set up parent slug
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
        if (!$this['parent_slug']->label()) {
            $this['parent_slug']->addClass('hidden');
        } else {
            $this['parent_slug']->removeClass('hidden');
        }
        return parent::default();
    }

    //trims off leading/trailing slashes
    public function value($value = null)
    {
        if (is_string($value)) {
            if ($value) {
                if ($this['parent_slug']->label() && strpos($value, $this['parent_slug']->label()) === 0) {
                    $this['parent_slug']->value(true);
                    $this['my_slug']->value(substr($value, strlen($this['parent_slug']->label())));
                } else {
                    $this['parent_slug']->value(false);
                    $this['my_slug']->value($value);
                }
            }
        } else {
            parent::value($value);
        }
        $this['my_slug']->value(
            trim($this['my_slug']->value(), "\/ \t\n\r\0\x0B")
        );
        if (!$this['parent_slug']->label()) {
            $this['parent_slug']->addClass('hidden');
        } else {
            $this['parent_slug']->removeClass('hidden');
        }
        return parent::value();
    }
}
