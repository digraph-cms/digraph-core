<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */

namespace Digraph\Forms;

use Digraph\DSO\NounInterface;
use Digraph\Forms\Fields\CodeEditor;
use Digraph\Forms\Fields\Content;
use Digraph\Forms\Fields\ContentDefault;
use Digraph\Forms\Fields\DateAndTimeRange;
use Digraph\Forms\Fields\DateAutocomplete;
use Digraph\Forms\Fields\DateTimeAutocomplete;
use Digraph\Forms\Fields\FieldValueAutocomplete;
use Digraph\Forms\Fields\Noun;
use Digraph\Forms\Fields\SlugPattern;
use Digraph\Forms\Fields\User;
use Digraph\Helpers\AbstractHelper;
use Flatrr\FlatArray;
use Formward\Fields\Checkbox;
use Formward\Fields\INI;
use Formward\Fields\Input;
use Formward\Fields\JSON;
use Formward\Fields\Select;
use Formward\Fields\Textarea;
use Formward\Fields\YAML;

class FormHelper extends AbstractHelper
{
    protected $types = [
        'digraph_content' => Content::class,
        'digraph_content_default' => ContentDefault::class,
        'digraph_name' => Input::class,
        'digraph_slug' => SlugPattern::class,
        'digraph_title' => Input::class,
        'array' => YAML::class,
        'checkbox' => Checkbox::class,
        'date' => DateAutocomplete::class,
        'datetime' => DateTimeAutocomplete::class,
        'datetime_range' => DateAndTimeRange::class,
        'fieldvalue' => FieldValueAutocomplete::class,
        'ini' => INI::class,
        'json' => JSON::class,
        'noun' => Noun::class,
        'select' => Select::class,
        'text' => Input::class,
        'textarea' => Textarea::class,
        'user' => User::class,
        'yaml' => YAML::class,
        'code' => CodeEditor::class
    ];

    public function form($label = '', $name = null)
    {
        $form = new Form($label, $name);
        $form->cms($this->cms);
        return $form;
    }

    public function registerType($name, $class)
    {
        $this->types[$name] = $class;
    }

    public function field($type, $label, $extraArgs = [])
    {
        $class = isset($this->types[$type]) ? $this->types[$type] : $type;
        if (!class_exists($class)) {
            throw new \Exception("Class not found for field type $type, class $class");
        }
        //set up args
        $args = [$label, null, null, $this->cms];
        if ($extraArgs) {
            foreach ($extraArgs as $a) {
                $args[] = $a;
            }
        }
        //create new ReflectionClass from class requested
        //and use it to instantiate the class with the args
        $r = new \ReflectionClass($class);
        $field = $r->newInstanceArgs($args);
        return $field;
    }

    protected function mapNoun(NounInterface $noun, Form $form, array $map, NounInterface $parent = null)
    {
        $form->object = $noun;
        foreach ($map as $name => $opt) {
            if (!$opt) {
                continue;
            }
            //create new field
            $field = $this->field($opt['class'], $opt['label'], @$opt['extraConstructArgs']);
            //tell field about the noun if the field has the dsoNoun method
            if (method_exists($field, 'dsoNoun')) {
                $field->dsoNoun($noun);
            }
            //tell field about the noun if the field has the dsoParent method
            if (!$parent) {
                $parent = $noun->parent();
            }
            if ($parent && method_exists($field, 'dsoParent')) {
                $field->dsoParent($parent);
            }
            //allow map to call functions on field
            if (@$opt['call']) {
                foreach ($opt['call'] as $fn => $args) {
                    call_user_func_array([$field, $fn], $args);
                }
            }
            //mark as required
            if (@$opt['required']) {
                $field->required(true);
            }
            //set up options if available
            if (@$opt['options'] && method_exists($field, 'options')) {
                $field->options($opt['options']);
            }
            //set default value
            $field->default(@$opt['default']);
            //set up tips
            if (@$opt['tips']) {
                foreach ($opt['tips'] as $key => $value) {
                    $field->addTip($value, 'mapped_' . $key);
                }
            }
            //set up CSS classes
            if (@$opt['cssClasses']) {
                foreach ($opt['cssClasses'] as $value) {
                    $field->addClass($value);
                }
            }
            //set default from noun value at location set in map ['field']
            if (@$opt['field']) {
                $field->default($noun[@$opt['field']]);
            }
            //add to form
            $form[$name] = $field;
        }
        //set up function writing content to object
        $form->digraphHandlerFn = function () use ($noun, $form, $map) {
            foreach ($map as $name => $opt) {
                if (method_exists($form[$name], 'hook_formWrite')) {
                    $form[$name]->hook_formWrite($noun, $opt);
                } elseif (method_exists($form[$name], 'dsoValue')) {
                    $noun[$opt['field']] = $form[$name]->dsoValue();
                } else {
                    $noun[$opt['field']] = $form[$name]->value();
                }
            }
        };
    }

    public function getMap(NounInterface $noun, string $action = 'all')
    {
        //load default map
        $map = new FlatArray($this->cms->config['forms.maps.default']);
        //load map from object
        $map->merge($noun->formMap($action), null, true);
        //load type map
        $map->merge($this->cms->config['forms.maps.' . $noun['dso.type'] . '.all'], null, true);
        //load type/action map
        $map->merge($this->cms->config['forms.maps' . $noun['dso.type'] . '.' . $action], null, true);
        $map = $map->get();
        $map = array_filter($map);
        uasort(
            $map,
            function ($a, $b) {
                if (@$a['weight'] == @$b['weight']) {
                    return 0;
                } elseif (@$a['weight'] < @$b['weight']) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );
        return $map;
    }

    public function mapForm(NounInterface $noun, array $map, string $name = 'default'): Form
    {
        $form = new Form('', 'mapForm-' . $noun['dso.id'] . '-' . $name);
        $form->cms($this->cms);
        $form->addClass('mapForm');
        $this->mapNoun(
            $noun,
            $form,
            $map,
            $noun->parent()
        );
        return $form;
    }

    public function editNoun(NounInterface $noun): Form
    {
        $form = new Form('', 'edit-' . $noun['dso.id']);
        $form->cms($this->cms);
        $form->addClass('editNoun');
        $this->mapNoun(
            $noun,
            $form,
            $this->getMap($noun, 'edit')
        );
        return $form;
    }

    public function addNoun(string $type, NounInterface $parent = null, string $factory = 'content'): Form
    {
        $noun = $this->cms->factory($factory)->create(['dso.type' => $type]);
        $form = new Form('', 'add-' . $type);
        $form->cms($this->cms);
        $form->addClass('addNoun');
        $this->mapNoun(
            $noun,
            $form,
            $this->getMap($noun, 'add'),
            $parent
        );
        return $form;
    }
}
