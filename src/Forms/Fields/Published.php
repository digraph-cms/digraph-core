<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;
use Formward\Fields\Select;
use Formward\Fields\Container;
use Formward\Fields\DateAndTime;
use \DateTime;

class Published extends Container
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
        $this['mode'] = new Select('publish');
        $this['mode']->required(true);
        $this['mode']->options([
            'on' => 'on',
            'off' => 'off',
            'date' => 'date'
        ]);
        $this['start'] = new DateAndTime('start');
        $this['end'] = new DateAndTime('end');
    }

    public function cms(&$cms)
    {
        $s = $cms->helper('strings');
        $this['mode']->label($s->string('forms.digraph_published.label'));
        $this['mode']->options($cms->config['strings.forms.digraph_published.options']);
        $this['start']->label($s->string('forms.digraph_published.label_start'));
        $this['end']->label($s->string('forms.digraph_published.label_end'));
    }

    public function dsoValue()
    {
        $value = [];
        //mode/force
        $mode = $this['mode']->value();
        if ($mode == 'on') {
            $value['force'] = 'published';
        }
        if ($mode == 'off') {
            $value['force'] = 'unpublished';
        }
        //start
        if ($start = $this['start']->value()) {
            $value['start'] = $start->getTimestamp();
        }
        //end
        if ($end = $this['end']->value()) {
            $value['end'] = $end->getTimestamp();
        }
        //return array
        return $value;
    }

    public function default($set = null)
    {
        return $this->translateMethod('default', $set);
    }

    public function translateMethod($method, $set)
    {
        $set['mode'] = 'date';
        if (@$set['force'] === 'published') {
            $set['mode'] = 'on';
        }
        if (@$set['force'] === 'unpublished') {
            $set['mode'] = 'off';
        }
        if (isset($set['start'])) {
            $time = $set['start'];
            $set['start'] = new DateTime();
            $set['start']->setTimeStamp($time);
        }
        if (isset($set['end'])) {
            $time = $set['end'];
            $set['end'] = new DateTime();
            $set['end']->setTimeStamp($time);
        }
        return parent::$method($set);
    }
}
