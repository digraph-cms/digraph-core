<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Forms;

use Formward\FieldInterface;

class ConfirmationForm extends \Formward\Form
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null)
    {
        parent::__construct('', $name, $parent);
        $this->submitButton()->label($label);
    }
}
