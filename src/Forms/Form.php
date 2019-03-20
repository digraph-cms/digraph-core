<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms;

use Formward\FieldInterface;
use Digraph\DSO\NounInterface;
use Digraph\CMS;

class Form extends \Formward\Form
{
    public $writeObjectFn;
    public $written = false;
    public $object;
    public $parent;
    protected $cms;

    public function __construct(string $label, string $name=null, FieldInterface $parent=null)
    {
        parent::__construct($label, $name, $parent);
        $this->attr('enctype', 'multipart/form-data');
    }

    public function &cms(CMS &$set = null) : CMS
    {
        if ($set) {
            $this->cms = $set;
        }
        return $this->cms;
    }

    public function set(?string $name, $value)
    {
        if (method_exists($value, 'cms')) {
            $value->cms($this->cms);
        }
        parent::set($name, $value);
    }

    public function handle(callable $validFn = null, callable $invalidFn = null, callable $notSubmittedFn = null) : ?bool
    {
        if ($out = parent::handle($validFn, $invalidFn, $notSubmittedFn)) {
            if (!$this->written && $this->writeObjectFn) {
                $this->written = ($this->writeObjectFn)();
            }
        }
        return $out;
    }
}
