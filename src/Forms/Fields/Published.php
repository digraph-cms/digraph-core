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
        $this['mode'] = new Select('Publish mode');
        $this['mode']->required(true);
        $this['mode']->options([
            'on' => 'Published',
            'off' => 'Unpublished',
            'date' => 'By date'
        ]);
        $this['start'] = new DateAndTime('Start date');
        $this['end'] = new DateAndTime('End date');
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
        if (@$set['force'] === true) {
            $set['mode'] = 'on';
        }
        if (@$set['force'] === false) {
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
