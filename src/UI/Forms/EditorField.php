<?php
namespace DigraphCMS\UI\Forms;

use DigraphCMS\Editor\Editor;
use Formward\Fields\Textarea;

class EditorField extends Textarea
{
    function construct()
    {
        Editor::load();
        $this->addClass('editorjs');
    }
}
