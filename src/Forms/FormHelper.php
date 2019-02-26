<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms;

use Digraph\Helpers\AbstractHelper;
use Digraph\DSO\NounInterface;
use Flatrr\FlatArray;

class FormHelper extends AbstractHelper
{
    protected function mapNoun(NounInterface &$noun, Form &$form, array $map, bool $insert = false)
    {
        foreach ($map as $name => $opt) {
            if (!$opt) {
                continue;
            }
            $args = [$opt['label'],null,null,&$this->cms];
            if (@$opt['extraConstructArgs']) {
                foreach ($opt['extraConstructArgs'] as $a) {
                    $args[] = $a;
                }
            }
            $r = new \ReflectionClass($opt['class']);
            $field = $r->newInstanceArgs($args);
            if (method_exists($field, 'dsoNoun')) {
                $field->dsoNoun($noun);
            }
            if (@$map['call']) {
                foreach ($map['callFns'] as $fn => $args) {
                    call_user_func_array([$field,$fn], $args);
                }
            }
            if (@$opt['required']) {
                $field->required(true);
            }
            $field->default(@$opt['default']);
            if (@$opt['field']) {
                //field isn't actually required
                $field->default($noun[@$opt['field']]);
            }
            $form[$name] = $field;
        }
        $form->object = $noun;
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

    public function addNoun(string $type, string $parent = null) : Form
    {
        $noun = $this->cms->factory()->create(['dso.type'=>$type]);
        if ($parent) {
            $noun->addParent($parent);
        }
        $form = new Form('', 'add-'.$type);
        $form->cms($this->cms);
        $form->addClass('addNoun');
        $this->mapNoun(
            $noun,
            $form,
            $this->getMap($noun, 'add'),
            true
        );
        return $form;
    }
}
