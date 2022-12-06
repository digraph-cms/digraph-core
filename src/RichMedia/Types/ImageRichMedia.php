<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\FIGURE;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTML\Icon;
use DigraphCMS\HTML\IMG;
use DigraphCMS\HTML\ResponsivePicture;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\RichContent\RichContentField;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ImageRichMedia extends AbstractRichMedia
{

    public function icon()
    {
        return new Icon('image');
    }

    public function prepareForm(FormWrapper $form, $create = false)
    {
        // current file info
        if ($this->file()) {
            try {
                $preview = new ResponsivePicture($this->file()->image(), 'Preview of uploaded image');
                $preview->setExpectedWidth(50);
                $preview->setMaxHeight(30);
                $form->addChild($preview);
            } catch (\Throwable $th) {
            }
        }

        // name input
        $name = (new Field('Name'))
            ->addTip('Leave blank to use the filename of the uploaded file')
            ->setDefault($this->name())
            ->addForm($form);

        // upload field
        $file = (new Field($create ? 'File' : 'Replace file', new UploadSingle()))
            ->addForm($form);
        if ($create) $file->setRequired(true);

        $alt = (new Field('Alt text'))
            ->setRequired(true)
            ->setDefault($this['alt'])
            ->addForm($form);

        $caption = (new RichContentField('Caption', $this->uuid(), true))
            ->addClass('rich-content-editor--mini')
            ->setDefault($this->caption())
            ->addForm($form);

        // callback for taking in values
        $form->addCallback(function () use ($name, $file, $alt, $caption) {
            // upload/replace file
            if ($file->value()) {
                if ($this->file()) $this->file()->delete();
                $this['file'] = $file->input()->filestore($this->uuid())->uuid();
            }
            // set name
            $this->name($name->value() ? $name->value() : $this->file()->filename());
            // alt and caption
            $this->setCaption($caption->value());
            $this['alt'] = $alt->value();
        });
    }

    public function shortCode(ShortcodeInterface $code): ?string
    {
        // if this is a plain image, just return a straight image
        if ($code->getParameter('plain', 'no') !== 'no') {
            $file = $this->file()->image();
            $image = new IMG($file->url(), $this['alt']);
            if ($code->getParameter('floated', 'no') !== 'no') {
                $image->addClass('floated');
            }
            return $image->__toString();
        }
        // try to render responsive picture
        try {
            $image = new ResponsivePicture(
                $this->file()->image(),
                $this['alt']
            );
        } catch (\Throwable $th) {
            return (new DIV)
                ->addClass('notification')
                ->addClass('notification--error')
                ->addChild('Exception occurred while rendering image')
                ->toString();
        }
        // wrap in a figure tag if necessary
        if ($code->getContent()) {
            $figure = (new FIGURE)
                ->addChild($image)
                ->addChild('<figcaption>' . $code->getContent() . '</figcaption>');
        } elseif ($this->caption()->source()) {
            $figure = (new FIGURE)
                ->addChild($image)
                ->addChild('<figcaption>' . $this->caption() . '</figcaption>');
        }
        if (isset($figure)) {
            $figure->setAttribute('style', implode(';', [
                'background-color:' . $this->file()->image()->color(),
                'background-image:url("' . $this->file()->image()->previewBackgroundUrl() . '")',
                'background-size:cover',
                'background-position: center center',
            ]));
            $out = $figure;
        } else {
            $out = $image;
        }
        // set to float if specified
        if ($code->getParameter('floated', 'no') !== 'no') {
            $out->addClass('floated');
        }
        // return string
        return $out->__toString();
    }

    public function setCaption(RichContent $caption = null)
    {
        unset($this['caption']);
        if ($caption) {
            $this['caption'] = $caption->array();
        }
    }

    public function caption(): ?RichContent
    {
        if ($this['caption']) {
            return new RichContent($this['caption']);
        }
        return null;
    }

    public static function className(): string
    {
        return 'Image';
    }

    public static function description(): string
    {
        return 'Upload a single image to embed or post as a download';
    }

    public function name(string $set = null): string
    {
        if ($set) {
            $this->name = $set;
        }
        return $this->name ?? $this->file()->filename();
    }

    public function file(): ?FilestoreFile
    {
        return Filestore::get($this['file']);
    }
}
