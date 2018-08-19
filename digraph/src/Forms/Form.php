<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Forms;

use Digraph\Forms\FieldInterface;
use Digraph\CMS\DSO\NounInterface;

class Form extends \Digraph\Forms\Form
{
    public $writeDSOfn;
    public $dso;

    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
    }

    public function handle(callable $validFn = null, callable $invalidFn = null, callable $notSubmittedFn = null) : ?bool
    {
        if ($out = parent::handle($validFn, $invalidFn, $notSubmittedFn)) {
            if ($this->writeDSOfn) {
                ($this->writeDSOfn)();
            }
        }
        return $out;
    }
}
