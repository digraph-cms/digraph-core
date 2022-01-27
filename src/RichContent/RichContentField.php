<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\CodeMirror\CodeMirrorInput;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;

class RichContentField extends Field
{
    protected $pageUuid, $wrapper, $contentEditor, $mediaEditor, $mediaEditorFrame, $toolbarFrame;

    public function __construct(string $label, string $pageUuid = null)
    {
        parent::__construct($label, new CodeMirrorInput());
        $this->setPageUuid($pageUuid);
        // set up markup to scaffold the JS editor features
        $this->addClass('rich-content-editor');
        $this->wrapper = (new DIV())
            ->addClass('rich-content-editor__dynamic-editor');
        $this->contentEditor = (new DIV())
            ->addClass('rich-content-editor__content-editor');
        $this->wrapper->addChild($this->contentEditor);
        $this->mediaEditor = (new DIV())
            ->addClass('rich-content-editor__media-editor');
        $this->mediaEditorFrame = (new DIV())
            ->addClass('rich-content-editor__media-editor-frame')
            ->addClass('navigation-frame')
            ->addClass('navigation-frame--stateless')
            ->setData('target', '_frame');
        $this->mediaEditor->addChild($this->mediaEditorFrame);
        $this->wrapper->addChild($this->mediaEditor);
        $this->addChild($this->wrapper);
        // add toolbar
        $this->toolbarFrame = (new DIV())
            ->addClass('rich-content-editor__toolbar')
            ->addClass('toolbar')
            ->addClass('navigation-frame')
            ->addClass('navigation-frame--stateless')
            ->setData('target', '_frame');
        $this->contentEditor->addChild($this->toolbarFrame);
        // add editor wrapper
        $this->contentEditor->addChild(
            (new DIV)
                ->addClass('rich-content-editor__content-editor__editor')
        );
        // add basic tips
        $this->addTip(sprintf(
            'Content can be formatted with <a href="%s" target="_lightbox">Markdown</a> and <a href="%s" target="_lightbox">ShortCodes</a>',
            new URL('/~markdown/'),
            new URL('/~shortcodes/')
        ));
    }

    public function pageUuid(): ?string
    {
        return $this->pageUuid;
    }

    /**
     * Undocumented function
     *
     * @param string|null $pageUuid
     * @return $this
     */
    public function setPageUuid(?string $pageUuid)
    {
        $this->pageUuid = $pageUuid;
        return $this;
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
        $uuid = $this->pageUuid();
        $this->mediaEditorFrame
            ->setID("rm_$id")
            ->setData('initial-source', new URL("/~api/v1/rich-media/?frame=rm_$id&uuid=$uuid"));
        $this->toolbarFrame
            ->setID("tb_$id")
            ->setData('initial-source', new URL("/~api/v1/rich-media/toolbar/?frame=tb_$id&uuid=$uuid"));
        // return normally
        return parent::toString();
    }
}
