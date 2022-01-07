<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\TEXTAREA;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;

class RichContentField extends Field
{
    protected $wrapper, $contentEditor, $insertEditor, $insertEditorFrame;

    public function __construct(string $label)
    {
        parent::__construct($label, new TEXTAREA());
        // set up markup to scaffold the JS editor features
        $this->addClass('rich-content-editor');
        $this->wrapper = (new DIV())
            ->addClass('rich-content-editor__dynamic-editor');
        $this->contentEditor = (new DIV())
            ->addClass('rich-content-editor__content-editor');
        $this->wrapper->addChild($this->contentEditor);
        $this->insertEditor = (new DIV())
            ->addClass('rich-content-editor__media-editor');
        $this->insertEditorFrame = (new DIV())
            ->addClass('rich-content-editor__media-editor-frame')
            ->addClass('navigation-frame')
            ->addClass('navigation-frame--stateless')
            ->setData('target', '_frame');
        $this->insertEditor->addChild($this->insertEditorFrame);
        $this->wrapper->addChild($this->insertEditor);
        $this->addChild($this->wrapper);
        // load theme elements
        Theme::addBlockingPageCss('/forms/rich-content/*.css');
        Theme::addBlockingPageJs('/forms/rich-content/*.js');
    }

    public function setDefault($default)
    {
        if ($default instanceof RichContent) {
            $default = $default->value();
        }
        parent::setDefault($default);
        return $this;
    }

    public function value($useDefault = false): ?RichContent
    {
        return new RichContent($this->input()->value($useDefault));
    }

    public function default(): ?RichContent
    {
        return new RichContent($this->input()->default());
    }

    public function toString(): string
    {
        $id = md5($this->id());
        $this->insertEditorFrame
            ->setID($id)
            ->setData('initial-source', new URL('/~api/v1/rich-media/?frame=' . $id));
        // return normally
        return parent::toString();
    }
}
