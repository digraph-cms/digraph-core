<?php

namespace DigraphCMS\CodeMirror;

use DigraphCMS\Config;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\TEXTAREA;

class CodeMirrorInput extends TEXTAREA
{
    protected $wrapper;
    protected $mode = 'gfm';
    protected $config = [];

    public function __construct()
    {
        $this->wrapper = new DIV;
        $this->wrapper->addChild('%s');
        $this->wrapper->addClass('codemirror-input-wrapper');
    }

    protected function mode(): string
    {
        return $this->mode;
    }

    public function config(): array
    {
        return array_merge(
            Config::get(sprintf('codemirror.mode.%s.config', $this->mode())),
            $this->config
        );
    }

    public function toString(): string
    {
        CodeMirror::loadMode($this->mode());
        $this->wrapper->setData('codemirror-mode', $this->mode);
        $this->wrapper->setData('codemirror-config', json_encode($this->config()));
        return sprintf(
            $this->wrapper->__toString(),
            parent::toString()
        );
    }
}
