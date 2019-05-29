<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms;

use Digraph\Helpers\AbstractHelper;
use Digraph\DSO\NounInterface;
use Flatrr\FlatArray;

class FormHelper extends AbstractHelper
{
    protected $types = [
        'digraph_content' => Fields\Content::class,
        'digraph_slug' => Fields\SlugPattern::class,
        'digraph_name' => \Formward\Fields\Input::class,
        'digraph_title' => \Formward\Fields\Input::class,
        'checkbox' => \Formward\Fields\Checkbox::class,
        'datetime' => \Formward\Fields\DateAndTime::class,
        'select' => \Formward\Fields\Select::class,
        'text' => \Formward\Fields\Input::class,
    ];

    public function form($label='', $name=null)
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
        $class = isset($this->types[$type])?$this->types[$type]:$type;
        if (!class_exists($class)) {
            throw new \Exception("Class not found for field type $type, class $class");
        }
        //set up args
        $args = [$label,null,null,&$this->cms];
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

    protected function mapNoun(NounInterface &$noun, Form &$form, array $map, NounInterface &$parent = null)
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
            if (@$map['call']) {
                foreach ($map['call'] as $fn => $args) {
                    call_user_func_array([$field,$fn], $args);
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
                    $field->addTip($value, 'mapped_'.$key);
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
        $form->digraphHandlerFn = function () use ($noun,$form,$map) {
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

    public function getMap(NounInterface &$noun, string $action = 'all')
    {
        //load default map
        $map = new FlatArray($this->cms->config['forms.maps.default']);
        //load map from object
        $map->merge($noun->formMap($action), null, true);
        //load type map
        $map->merge($this->cms->config['forms.maps.'.$noun['dso.type'].'.all'], null, true);
        //load type/action map
        $map->merge($this->cms->config['forms.maps'.$noun['dso.type'].'.'.$action], null, true);
        $map = $map->get();
        $map = array_filter($map);
        uasort(
            $map,
            function ($a, $b) {
                if ($a['weight'] == $b['weight']) {
                    return 0;
                } elseif ($a['weight'] < $b['weight']) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );
        return $map;
    }

    public function editNoun(NounInterface &$noun) : Form
    {
        $form = new Form('', 'edit-'.$noun['dso.id']);
        $form->cms($this->cms);
        $form->addClass('editNoun');
        $this->mapNoun(
            $noun,
            $form,
            $this->getMap($noun, 'edit')
        );
        return $form;
    }

    public function addNoun(string $type, NounInterface $parent = null) : Form
    {
        $noun = $this->cms->factory()->create(['dso.type'=>$type]);
        $form = new Form('', 'add-'.$type);
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
