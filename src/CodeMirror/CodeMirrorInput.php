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
    protected static $idCounter = 0;

    public function __construct()
    {
        $this->wrapper = new DIV;
        $this->wrapper->addChild('%s');
        $this->wrapper->addClass('codemirror-input-wrapper');
        $this->setID('codemirror-input--' . static::$idCounter++);
    }

    public function setMode(string $mode)
    {
        $this->mode = $mode;
        return $this;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function config(): array
    {
        return array_merge(
            Config::get(sprintf('codemirror.config.%s', $this->mode())) ?? [],
            $this->config
        );
    }

    public function toString(): string
    {
        CodeMirror::loadMode(
            (Config::get('codemirror.loadalias.' . $this->mode()))
                ?? $this->mode()
        );
        $this->wrapper->setData(
            'codemirror-mode',
            (Config::get('codemirror.modealias.' . $this->mode()))
                ?? $this->mode()
        );
        $this->wrapper->setData('codemirror-config', json_encode($this->config()));
        return sprintf(
            $this->wrapper->__toString(),
            parent::toString()
        );
    }
}
