<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;
use Formward\Fields\Checkbox;
use Formward\Fields\Container;
use Formward\Fields\Input;

class SlugPattern extends Container
{
    //The characters allowed in addition to alphanumerics and slashes
    const CHARS = '$-_.+!*\'(),';

    protected $cms;
    protected $noun;
    protected $pnoun;
    protected $vars = [];

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, &$cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this->cms = $cms;
        $this['use'] = new Checkbox('Enable custom URL');
        $this['use']->default(true);
        $this['slug'] = new Input('');
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
                if (preg_match('/[^a-z0-9\[\]\/'.preg_quote(static::CHARS).']/i', $value)) {
                    return $this->cms->helper('strings')->string('forms.slug.error.character', [static::CHARS]);
                }
                return true;
            }
        );
    }

    public function disableByDefault(bool $set=null)
    {
        $this['use']->default(false);
    }

    public function dsoNoun(&$noun)
    {
        $this->noun = $noun;
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
        $this->pnoun = $parent;
    }

    public function default($value = null)
    {
        if (!$value) {
            $value = null;
        } else {
            $this['use']->default(true);
        }
        return $this['slug']->default($value);
    }

    public function hook_formWrite($noun, $opt)
    {
        $this->dsoNoun($noun);
        $noun[$opt['field']] = $this->dsoValue();
        //verify that this slug hasn't been used before
        if ($value = $this->dsoValue()) {
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
        }
    }

    public function dsoValue()
    {
        if (!$this['use']->value()) {
            return false;
        }
        return $this->vars($this['slug']->value());
    }

    public function vars($slug)
    {
        //pull vars from parent
        if ($this->pnoun) {
            $this->vars['parent'] = $this->pnoun->url()['noun'];
            $this->vars['parentid'] = $this->pnoun['dso.id'];
            if (method_exists($this->pnoun, 'slugVars')) {
                foreach ($this->pnoun->slugVars() as $key => $value) {
                    $this->vars[$key] = $value;
                }
            }
        }
        // pull vars from noun
        if ($this->noun) {
            $this->vars['id'] = $this->noun['dso.id'];
            $this->vars['name'] = $this->noun->name();
            $this->vars['cdate'] = '[cdate-year][cdate-month][cdate-day]';
            $this->vars['cdate-time'] = '[cdate-hour][cdate-minute]';
            $this->vars['cdate-year'] = date('Y', $this->noun['dso.created.date']);
            $this->vars['cdate-month'] = date('m', $this->noun['dso.created.date']);
            $this->vars['cdate-day'] = date('d', $this->noun['dso.created.date']);
            $this->vars['cdate-hour'] = date('H', $this->noun['dso.created.date']);
            $this->vars['cdate-minute'] = date('i', $this->noun['dso.created.date']);
            if ($this->noun['digraph.slugpattern']) {
                $this['use']->default(true);
                $this['slug']->default($this->noun['digraph.slugpattern']);
            }
        }
        //do variable replacement
        $slug = $this['slug']->value();
        foreach ($this->vars as $key => $value) {
            $slug = str_replace('['.$key.']', $value, $slug);
        }
        //clean up
        $slug = trim($slug, "\/ \t\n\r\0\x0B");
        $slug = preg_replace('/[^a-z0-9\/'.preg_quote(static::CHARS).']+/i', '-', $slug);
        $slug = preg_replace('/\-?\/\-?/', '/', $slug);
        $slug = preg_replace('/\/+/', '/', $slug);
        $slug = preg_replace('/\-+/', '-', $slug);
        $slug = preg_replace('/[\/\-]+$/', '', $slug);
        $slug = preg_replace('/^[\/\-]+/', '', $slug);
        $slug = preg_replace('/^home\//', '', $slug);
        $slug = strtolower($slug);
        return $slug;
    }
}
