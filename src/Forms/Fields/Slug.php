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

    protected $cms;
    protected $noun;
    protected $vars = [];

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, &$cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this->cms = $cms;
        $this['use'] = new Checkbox('Enable custom URL');
        $this['slug'] = new Input('');
        $this['slug']->default('[parent]/[name]');
        //add validator function to warn for non-unique slug
        $this->addValidatorFunction(
            'unique',
            function ($field) {
                $value = $this->dsoValue();
                if (!$value) {
                    return true;
                }
                $res = $this->cms->read($value);
                if ($res && $res['dso.id'] != $this->noun['dso.id']) {
                    $this->cms->helper('notifications')->flashWarning(
                        $this->cms->helper('strings')->string(
                            'forms.slug.error.taken',
                            [
                                'slug' => $value,
                                'by' => $res->url()->html()
                            ]
                        )
                    );
                }
                return true;
            }
        );
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

    public function dsoNoun(&$noun)
    {
        $this->noun = $noun;
        $this->vars['id'] = $noun['dso.id'];
        $this->vars['name'] = $noun->name();
        if ($noun['digraph.slug']) {
            $this['use']->default(true);
            $this['slug']->default($noun['digraph.slug']);
        }
        if ($noun->parent()) {
            $this->dsoParent($noun->parent());
        }
        if (method_exists($noun, 'slugVars')) {
            foreach ($noun->slugVars() as $key => $value) {
                $this->vars[$key] = $value;
            }
        }
    }

    public function dsoParent(&$parent)
    {
        $this->vars['parent'] = $parent->url()['noun'];
        $this->vars['parentid'] = $parent['dso.id'];
        if (method_exists($parent, 'slugVars')) {
            foreach ($parent->slugVars() as $key => $value) {
                $this->vars[$key] = $value;
            }
        }
    }

    public function default($value = null)
    {
        $this['slug']->attr('data-slug-pattern', $value);
    }

    public function dsoValue()
    {
        if (!$this['use']->value()) {
            return null;
        }
        return $this->vars($this['slug']->value());
    }

    public function vars($slug)
    {
        $slug = $this['slug']->value();
        foreach ($this->vars as $key => $value) {
            $slug = str_replace('['.$key.']', $value, $slug);
        }
        $slug = trim($slug, "\/ \t\n\r\0\x0B");
        $slug = preg_replace('/[^a-z0-9\/'.preg_quote(static::CHARS).']+/i', '-', $slug);
        return $slug;
    }

    // public function dsoValue()
    // {
    //     if (!$this['my_slug']->value()) {
    //         return null;
    //     }
    //     $slug = $this['parent_slug']->value()?$this['parent_slug']->label():'';
    //     $slug .= $this['my_slug']->value();
    //     $slug = trim($slug, "\/ \t\n\r\0\x0B");
    //     return $slug;
    // }
    //
    // public function dsoParent(&$parent)
    // {
    //     $this->parent = $parent;
    //     //set up parent slug
    //     $url = $parent->url();
    //     $this['parent_slug']->label($url['noun'].'/');
    //     if ($this['parent_slug']->label() == 'home/' || $this['parent_slug']->label() == '/') {
    //         $this['parent_slug']->label('');
    //         $this['parent_slug']->addClass('hidden');
    //     }
    // }
    //
    // public function dsoNoun(&$noun)
    // {
    //     $this->noun = $noun;
    //     //set up my slug
    //     $this['my_slug']->default($noun['dso.id']);
    //     //set up parent
    //     if ($noun->parent()) {
    //         $this->dsoParent($noun->parent());
    //     }
    // }
    //
    // public function default($value = null)
    // {
    //     if (is_string($value)) {
    //         if ($value) {
    //             if ($this['parent_slug']->label() && strpos($value, $this['parent_slug']->label()) === 0) {
    //                 $this['parent_slug']->default(true);
    //                 $this['my_slug']->default(substr($value, strlen($this['parent_slug']->label())));
    //             } else {
    //                 $this['parent_slug']->default(false);
    //                 $this['my_slug']->default($value);
    //             }
    //         }
    //     } else {
    //         parent::default($value);
    //     }
    //     if (!$this['parent_slug']->label()) {
    //         $this['parent_slug']->addClass('hidden');
    //     } else {
    //         $this['parent_slug']->removeClass('hidden');
    //     }
    //     return parent::default();
    // }

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
