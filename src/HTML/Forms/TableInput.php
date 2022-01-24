<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\CodeMirror\CodeMirror;
use DigraphCMS\Digraph;

class TableInput extends INPUT
{
    protected $default = null;

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'hidden',
                'value' => json_encode($this->value(true))
            ]
        );
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'table-input'
            ]
        );
    }

    public function default()
    {
        if ($this->default === null) {
            $this->default = [
                'head' => [Digraph::uuid() => [Digraph::uuid() => '']],
                'body' => [Digraph::uuid() => [Digraph::uuid() => '']]
            ];
        }
        return parent::default();
    }

    public function value($useDefault = false)
    {
        $value = parent::value($useDefault);
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        return $value;
    }

    public function toString(): string
    {
        CodeMirror::loadMode('gfm');
        return parent::toString() .
            '<noscript><div class="notification notification--error">Table editing inputs require Javascript</div></noscript>';
    }
}
