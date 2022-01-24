<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\UI\Format;

class FileRichMedia extends AbstractRichMedia
{
    public function card(): DIV
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
                ->addChild($this->name())
                ->setAttribute('title', $file->filename() . ' (' . Format::filesize($file->bytes()) . ')')
                ->setAttribute('href', $file->url())));
        // add requested
        $meta = [];
        if (in_array('size', $this['meta'])) {
            $meta[] = Format::filesize($file->bytes());
        }
        if (in_array('uploader', $this['meta']) && in_array('upload_date', $this['meta'])) {
            $meta[] = 'uploaded ' . Format::date($file->created()) . ' by ' . $file->createdBy();
        } else {
            if (in_array('upload_date', $this['meta'])) {
                $meta[] = 'uploaded ' . Format::date($file->created());
            }
            if (in_array('uploader', $this['meta'])) {
                $meta[] = 'uploaded by ' . $file->createdBy();
            }
        }
        if (in_array('uploader', $this['meta'])) {
            $meta[] = 'MD5 ' . $file->hash();
        }
        $card->addChild((new DIV)
                ->addClass('file-card__meta')
                ->addChild(implode('; ', $meta))
        );
        return $card;
    }

    public static function class(): string
    {
        return 'file';
    }

    public static function className(): string
    {
        return 'File download';
    }

    public function name(string $set = null): string
    {
        if ($set) {
            $this->name = $set;
        }
        return $this->name ?? $this->file()->filename();
    }

    public function file(): FilestoreFile
    {
        return Filestore::get($this['file']);
    }
}
