<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms;

use Formward\FieldInterface;
use Digraph\DSO\NounInterface;

class Form extends \Formward\Form
{
    public $writeObjectFn;
    public $object;

    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
    }

    public function handle(callable $validFn = null, callable $invalidFn = null, callable $notSubmittedFn = null) : ?bool
    {
        if ($out = parent::handle($validFn, $invalidFn, $notSubmittedFn)) {
            if ($this->writeObjectFn) {
                ($this->writeObjectFn)();
            }
        }
        return $out;
    }
}
