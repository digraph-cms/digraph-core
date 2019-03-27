<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Formward\Fields\Textarea;
use Formward\FieldInterface;

class ContentTextarea extends Textarea
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
    }
}
