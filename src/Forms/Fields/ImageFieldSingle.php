<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Digraph\CMS;
use Digraph\DSO\Noun;
use Formward\AbstractField;
use Formward\FieldInterface;

class ImageFieldSingle extends FileStoreFieldSingle
{
    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS $cms=null, string $path=null, array $exts=null, int $maxSize=null)
    {
        parent::__construct($label,$name,$parent,$cms,$path,$exts,$maxSize);
        $this['upload']->attr('accept','image/*');
    }
}
