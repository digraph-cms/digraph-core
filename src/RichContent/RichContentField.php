<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\CodeMirror\CodeMirrorInput;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\UI\Sidebar\Sidebar;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;

class RichContentField extends Field
{
    protected $pageUuid, $wrapper, $contentEditor, $mediaEditor, $mediaEditorFrame, $toolbarFrame;

    public function __construct(string $label, string $pageUuid = null, bool $hideMediaEditor = false)
    {
        parent::__construct($label, new CodeMirrorInput());
        $this->setPageUuid($pageUuid);
        // set up markup to scaffold the JS editor features
        $this->addClass('rich-content-editor');
        $this->wrapper = (new DIV())
            ->addClass('rich-content-editor__dynamic-editor');
        $this->addChild($this->wrapper);
        $this->contentEditor = (new DIV())
            ->addClass('rich-content-editor__content-editor');
        $this->wrapper->addChild($this->contentEditor);
        // only add media editor if $hideMediaEditor is false
        if (!$hideMediaEditor) {
            if (Permissions::inMetaGroup('richmedia__edit')) {
                Sidebar::setActive(false);
                $this->wrapper->addClass('rich-content-editor__dynamic-editor--richmedia');
                $this->mediaEditor = (new DIV())
                    ->addClass('rich-content-editor__media-editor');
                $this->mediaEditorFrame = (new DIV())
                    ->addClass('rich-content-editor__media-editor-frame')
                    ->addClass('navigation-frame')
                    ->addClass('navigation-frame--stateless')
                    ->setData('target', '_frame');
                $this->mediaEditor->addChild($this->mediaEditorFrame);
                $this->wrapper->addChild($this->mediaEditor);
            }
        }
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
            new URL('/~manual/editing/markdown.html'),
            new URL('/~manual/editing/shortcodes.html')
        ));
        $this->addTip(sprintf(
            'For advanced content editor tips, see the <a href="%s" target="_lightbox">Editor keyboard shortcuts reference</a>',
            new URL('/~manual/editing/keyboard_shortcuts.html')
        ));
    }

    public function id(): ?string
    {
        static $idCounter = 0;
        if (!parent::id()) {
            $this->setID('rich-content-field--' . $idCounter++);
        }
        return parent::id();
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
            $default = $default->source();
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
        $id = Digraph::uuid(null, $this->id());
        $uuid = $this->pageUuid();
        if ($this->mediaEditor) {
            $this->mediaEditorFrame
                ->setID("b$id")
                ->setData('initial-source', new URL("/~richmedia/sidebar/?frame=b$id&uuid=$uuid"));
        }
        $this->toolbarFrame
            ->setID("t$id")
            ->setData('initial-source', new URL("/~api/v1/toolbar/?frame=t$id&uuid=$uuid"));
        // return normally
        return parent::toString();
    }
}
