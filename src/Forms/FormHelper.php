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
            $field = new $class($opt['label']);
            if (@$opt['required']) {
                $field->required(true);
            }
            $field->default(@$opt['default']);
            $field->default($noun[$opt['field']]);
            $form[$name] = $field;
        }
        $form->noun = $noun;
        $form->writeDSOfn = function () use ($noun,$form,$map,$insert) {
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
                $noun->insert();
            } else {
                $noun->update();
            }
        };
    }

    public function getMap(NounInterface &$noun, string $action = 'all')
    {
        $map = new FlatArray($this->cms->config['forms.defaultmap']);
        $map->merge($this->cms->config['forms.maps.'.$noun['dso.type'].'.all'], null, true);
        $map->merge($this->cms->config['forms.maps'.$noun['dso.type'].'.'.$action], null, true);
        return $map->get();
    }

    public function editNoun(NounInterface &$noun) : Form
    {
        $form = new Form('edit', 'edit-'.$noun['dso.id']);
        $this->mapNoun(
            $noun,
            $form,
            $this->getMap($noun, 'edit')
        );
        return $form;
    }

    public function addNoun(string $type) : Form
    {
        $noun = $this->cms->factory()->create(['dso.type'=>$type]);
        $form = new Form('edit', 'add-'.$type);
        $this->mapNoun(
            $noun,
            $form,
            $this->getMap($noun, 'edit'),
            true
        );
        return $form;
    }
}
