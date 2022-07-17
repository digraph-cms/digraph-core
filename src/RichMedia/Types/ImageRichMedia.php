<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\FIGURE;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTML\ResponsivePicture;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\RichContent\RichContentField;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ImageRichMedia extends AbstractRichMedia
{

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

    /**
     * Generate a shortcode rendering of this media
     *
     * @param ShortcodeInterface $code
     * @param self $media
     * @return string|null
     */
    public static function shortCode(ShortcodeInterface $code, $media): ?string
    {
        try {
            $image = new ResponsivePicture(
                $media->file()->image(),
                $media['alt']
            );
        } catch (\Throwable $th) {
            return (new DIV)
                ->addClass('notification')
                ->addClass('notification--error')
                ->addChild('Exception occurred while rendering image')
                ->toString();
        }
        if ($media->caption()->source()) {
            $figure = (new FIGURE)
                ->setAttribute('style', 'background-color:' . $media->file()->image()->color())
                ->addChild($image)
                ->addChild('<figcaption>' . $media->caption() . '</figcaption>');
            return $figure->toString();
        }
        return $image->toString();
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
