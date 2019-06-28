<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_submissions;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Email;
use Formward\Fields\Input;

class SubmitterField extends \Formward\Fields\Container
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        parent::__construct($label, $name, $parent);
        $this['firstname'] = new Input('First name');
        $this['firstname']->required(true);
        $this['lastname'] = new Input('Last name');
        $this['lastname']->required(true);
        $this['email'] = new Email('Email address');
        $this['email']->required(true);
    }
}
