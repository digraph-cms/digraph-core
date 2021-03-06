<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;
use Formward\Fields\Checkbox;
use Formward\Fields\Container;
use Formward\Fields\Input;

class SlugPattern extends Container
{
    protected $cms;
    protected $noun;
    protected $pnoun;

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, $cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this->cms = $cms;
        $this['use'] = new Checkbox('Enable custom URL pattern');
        $this['use']->default(true);
        $this['slug'] = new Input('');
        //add a validator to trim slugs and ensure they're valid
        $this->addValidatorFunction(
            'validurl',
            function ($field) {
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
                if (preg_match('/[^a-z0-9\[\]\/'.preg_quote($this->chars()).']/i', $value)) {
                    return $this->cms->helper('strings')->string('forms.slug.error.character', [$this->chars()]);
                }
                return true;
            }
        );
    }

    protected function chars()
    {
        return $this->cms->helper('slugs')::CHARS;
    }

    public function disableByDefault(bool $set=null)
    {
        $this['use']->default(false);
    }

    public function dsoNoun($noun)
    {
        $this->noun = $noun;
        if ($noun->parent()) {
            $parent = $noun->parent();
            $this->dsoParent($parent);
        }
    }

    public function dsoParent($parent)
    {
        $this->pnoun = $parent;
    }

    public function default($value = null)
    {
        if ($value !== null) {
            if (is_string($value)) {
                $this['use']->default(true);
                $this['slug']->default($value);
            }else {
                $this['use']->default($value);
            }
        }
        if ($this['use']->default()) {
            return $this['slug']->default();
        }else {
            return $this['use']->default();
        }
    }

    public function dsoValue()
    {
        if (!$this['use']->value()) {
            return false;
        }
        return $this['slug']->value();
    }
}
