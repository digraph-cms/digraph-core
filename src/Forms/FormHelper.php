<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms;

use Digraph\Helpers\AbstractHelper;
use Digraph\DSO\NounInterface;
use Flatrr\FlatArray;

class FormHelper extends AbstractHelper
{
    protected function mapNoun(NounInterface &$noun, Form &$form, array $map, bool $insert = false, NounInterface &$parent = null)
    {
        $form->object = $noun;
        $form->parent = $parent;
        foreach ($map as $name => $opt) {
            if (!$opt) {
                continue;
            }
            //add extra construction args from map
            $args = [$opt['label'],null,null,&$this->cms];
            if (@$opt['extraConstructArgs']) {
                foreach ($opt['extraConstructArgs'] as $a) {
                    $args[] = $a;
                }
            }
            //create new ReflectionClass from class requested by map ['class']
            //and use it to instantiate the class with the args
            $r = new \ReflectionClass($opt['class']);
            $field = $r->newInstanceArgs($args);
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
            //set default value
            $field->default(@$opt['default']);
            //set default from noun value at location set in map ['field']
            if (@$opt['field']) {
                $field->default($noun[@$opt['field']]);
            }
            //add to form
            $form[$name] = $field;
        }
        $form->writeObjectFn = function () use ($noun,$form,$map,$insert) {
            foreach ($map as $name => $opt) {
                if (!$opt) {
                    continue;
                }
                if (method_exists($form[$name], 'hook_formWrite')) {
                    $form[$name]->hook_formWrite($noun, $opt);
                } elseif (method_exists($form[$name], 'dsoValue')) {
                    $noun[$opt['field']] = $form[$name]->dsoValue();
                } else {
                    $noun[$opt['field']] = $form[$name]->value();
                }
            }
            if ($insert) {
                return $noun->insert();
            } else {
                return $noun->update();
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
        ksort($map);
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
            true,
            $parent
        );
        return $form;
    }
}
