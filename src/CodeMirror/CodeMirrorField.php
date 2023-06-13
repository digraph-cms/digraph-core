<?php

namespace DigraphCMS\CodeMirror;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\URL\URL;

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
        $this->addTip(sprintf(
            'For advanced code editor tips, see the <a href="%s" target="_lightbox">Editor keyboard shortcuts reference</a>',
            new URL('/manual/editing/keyboard_shortcuts.html')
        ));
    }
}
