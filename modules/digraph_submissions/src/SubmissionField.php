<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_submissions;

use Digraph\CMS;
use Formward\FieldInterface;

class SubmissionField extends \Formward\Fields\Container
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null)
    {
        parent::__construct($label, $name, $parent);
    }
}
