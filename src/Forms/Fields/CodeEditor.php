<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */

namespace Digraph\Forms\Fields;

use Formward\Fields\Textarea;
use Formward\FieldInterface;
use Digraph\CMS;

class CodeEditor extends Textarea
{
    protected static $ACE_INCLUDED = false;
    protected $mode;

    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms=null, string $mode = 'css')
    {
        parent::__construct($label, $name, $parent);
        $this->mode = $mode;
    }

    public function string(): string
    {
        $string = [];
        if (!static::$ACE_INCLUDED) {
            $string[] = '<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.4.12/src-min-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>';
            static::$ACE_INCLUDED = true;
        }
        $string[] = parent::string();
        $string[] = '<pre class="ace_editor" id="'.$this->name().'_ace">'.$this->htmlContent().'</pre>';
        $string[] = '<script>';
        $string[] = '(()=>{';
        $string[] = 'var editor = ace.edit("'.$this->name().'_ace");editor.session.setMode("ace/mode/'.$this->mode.'");';
        $string[] = 'var textarea = $("#'.$this->name().'");';
        $string[] = 'textarea.hide()';
        $string[] = 'editor.getSession().on("change",function(){textarea.val(editor.getSession().getValue());});';
        $string[] = '})();';
        $string[] = '</script>';
        return implode(PHP_EOL,$string);
    }
}
