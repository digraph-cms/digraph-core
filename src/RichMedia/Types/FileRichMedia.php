<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\UI\Format;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class FileRichMedia extends AbstractRichMedia
{

    public function prepareForm(FormWrapper $form, $create = false)
    {
        // current file info
        if ($this->file()) {
            $form->addChild(
                $this->card(['size', 'uploader', 'upload_date', 'md5'], true)
            );
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

        // metadata display options
        $meta = (new CheckboxListField(
            'Display metadata',
            [
                'size' => 'Filesize',
                'uploader' => 'Uploading user',
                'upload_date' => 'Upload date',
                'md5' => 'MD5 hash'
            ]
        ))
            ->setDefault($this['meta'] ?? [])
            ->addForm($form);

        // callback for taking in values
        $form->addCallback(function () use ($name, $file, $meta) {
            // upload/replace file
            if ($file->value()) {
                if ($this->file()) $this->file()->delete();
                $this['file'] = $file->input()->filestore($this->uuid())->uuid();
            }
            // set name
            $this->name($name->value() ? $name->value() : $this->file()->filename());
            // replace metadata settings
            unset($this['meta']);
            $this['meta'] = $meta->value();
        });
    }

    public function shortCode(ShortcodeInterface $code): ?string
    {
        if ($code->getParameter('inline') || $code->getContent()) {
            return (new A)
                ->setAttribute('href', $this->file()->url())
                ->setAttribute('title', $this->file()->filename() . ' (' . Format::filesize($this->file()->bytes()) . ')')
                ->addChild($code->getContent() ?? $this->file()->filename());
        } else {
            return $this->card();
        }
    }

    public function card(array $overrideMeta = null, $showFilename = true): DIV
    {
        $file = $this->file();
        $card = (new DIV())
            ->addClass('file-card')
            ->addClass('card')
            ->addClass('file-card--extension-' . $file->extension());
        // add card title
        $card->addChild((new DIV)
            ->addClass('card__title')
            ->addChild((new A)
                ->addChild($showFilename ? $this->file()->filename() : $this->name())
                ->setAttribute('title', $file->filename() . ' (' . Format::filesize($file->bytes()) . ')')
                ->setAttribute('href', $file->url())));
        // add requested
        $meta = [];
        if (in_array('size', $overrideMeta ?? $this['meta'])) {
            $meta[] = Format::filesize($file->bytes());
        }
        if (in_array('uploader', $overrideMeta ?? $this['meta']) && in_array('upload_date', $this['meta'])) {
            $meta[] = 'uploaded ' . Format::date($file->created()) . ' by ' . $file->createdBy();
        } else {
            if (in_array('upload_date', $overrideMeta ?? $this['meta'])) {
                $meta[] = 'uploaded ' . Format::date($file->created());
            }
            if (in_array('uploader', $overrideMeta ?? $this['meta'])) {
                $meta[] = 'uploaded by ' . $file->createdBy();
            }
        }
        if (in_array('uploader', $overrideMeta ?? $this['meta'])) {
            $meta[] = 'MD5 ' . $file->hash();
        }
        $card->addChild((new DIV)
                ->addClass('file-card__meta')
                ->addChild(implode('; ', $meta))
        );
        return $card;
    }

    public static function className(): string
    {
        return 'File download';
    }

    public static function description(): string
    {
        return 'Upload a single file to post as a download';
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
