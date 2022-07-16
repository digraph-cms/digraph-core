<?php

namespace DigraphCMS\CodeMirror;

use DigraphCMS\HTML\Forms\Field;

/**
 * @method CodeMirrorInput input()
 */
class CodeMirrorField extends Field
{
    public function __construct(string $label, string $mode)
    {
        $input = new CodeMirrorInput();
        $input->setMode($mode);
        parent::__construct($label, $input);
    }
}
