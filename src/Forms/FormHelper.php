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
            $class = $opt['class'];
            $field = new $class($opt['label'], null, null, $this->cms);
            if (method_exists($field, 'dsoNoun')) {
                $field->dsoNoun($noun);
            }
            if (@$opt['required']) {
                $field->required(true);
            }
            $field->default(@$opt['default']);
            $field->default($noun[$opt['field']]);
            $form[$name] = $field;
        }
        $form->object = $noun;
        $form->writeObjectFn = function () use ($noun,$form,$map,$insert) {
            foreach ($map as $name => $opt) {
                if (!$opt) {
                    continue;
                }
                if (method_exists($form[$name], 'dsoValue')) {
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
        $form = new Form(
            $this->cms->helper('lang')->string('forms.edit_title', ['type'=>$noun['dso.type']]),
            'edit-'.$noun['dso.id']
        );
        $form->cms($this->cms);
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
        $this->mapNoun(
            $noun,
            $form,
            $this->getMap($noun, 'edit'),
            true
        );
        return $form;
    }
}
