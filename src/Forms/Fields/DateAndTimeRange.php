<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\FieldInterface;
use Formward\Fields\DateAndTimeRange as FieldsDateAndTimeRange;

class DateAndTimeRange extends FieldsDateAndTimeRange
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
        $this->wrapContainerItems(true);
        $this->removeClass('inline-children');
        $this['start'] = new DateTimeAutocomplete('Start');
        $this['start']->removeTip('format');
        $this['end'] = new DateTimeAutocomplete('End');
        $this['end']->removeTip('format');
    }
}
