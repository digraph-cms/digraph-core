<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\FS;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use ZipArchive;

class MultiFileRichMedia extends AbstractRichMedia
{
    protected $zipFile;

    public static function class(): string
    {
        return 'multifile';
    }

    public static function className(): string
    {
        return 'Zip file';
    }

    public static function description(): string
    {
        return 'Upload multiple files to post as a Zip file download';
    }

    public function card(): DIV
    {
        $file = $this->zipFile();
        $card = (new DIV())
            ->addClass('file-card')
            ->addClass('multifile-card')
            ->addClass('card')
            ->addClass('file-card--extension-' . $file->extension());
        // add card title
        $card->addChild((new DIV)
            ->addClass('card__title')
            ->addChild((new A)
                ->addChild($this->name())
                ->setAttribute('title', $file->filename())
                ->setAttribute('href', $file->url())));
        // add requested
        $meta = [];
        if (in_array('uploader', $this['meta']) && in_array('upload_date', $this['meta'])) {
            $meta[] = 'updated ' . Format::date($this->updated()) . ' by ' . $this->updatedBy();
        } else {
            if (in_array('upload_date', $this['meta'])) {
                $meta[] = 'updated ' . Format::date($this->updated());
            }
            if (in_array('uploader', $this['meta'])) {
                $meta[] = 'updated by ' . $this->updatedBy();
            }
        }
        if ($meta) {
            $card->addChild((new DIV)
                    ->addClass('file-card__meta')
                    ->addChild(implode('; ', $meta))
            );
        }
        // add list of individual files if required and requested
        if ($this['options.single']) {
            $id = 'multifile__list-' . $this->uuid();
            $wrapper = (new DIV)
                ->setID($id)
                ->addClass('navigation-frame navigation-frame--stateless')
                ->addClass('multifile-card__list');
            if (!Context::arg($id) == 'open') {
                // display link to show all files
                $wrapper->addChild((new A)
                    ->setAttribute('href', new URL("&$id=open"))
                    ->setAttribute('rel', 'nofollow')
                    ->addChild('-- show files --'));
            } else {
                // list all files
                $list = "<ul>";
                foreach ($this->files() as $f) {
                    $list .= sprintf(
                        '<li><a href="%s" target="_blank" title="%s %s">%s</a></li>',
                        $f->url(),
                        $f->filename(),
                        Format::filesize($f->bytes()),
                        $f->filename()
                    );
                }
                $list .= "</ul>";
                $wrapper->addChild($list);
            }
            $card->addChild($wrapper);
        }
        return $card;
    }

    public function filename(): string
    {
        return preg_replace('/[^a-z0-9\-_ ]/i', '', $this->name()) . '.zip';
    }

    public function zipFile(): DeferredFile
    {
        if (!$this->zipFile) {
            $this->zipFile = new DeferredFile(
                $this->filename(),
                function (DeferredFile $file) {
                    FS::mkdir(dirname($file->path()));
                    $temp = preg_replace('/\.zip$/', '.' . Digraph::uuid() . '.zip', $file->path());
                    $zip = new ZipArchive;
                    $zip->open($temp, ZipArchive::CREATE);
                    foreach ($this->files() as $f) {
                        $zip->addFile($f->src(), $f->filename());
                    }
                    $zip->close();
                    FS::copy($temp, $file->path());
                    unlink($temp);
                },
                array_map(
                    function (FilestoreFile $file) {
                        return [$file->filename(), $file->hash()];
                    },
                    $this->files()
                )
            );
            $this->zipFile->write();
        }
        return $this->zipFile;
    }

    /**
     * Undocumented function
     *
     * @return FilestoreFile[]
     */
    public function files(): array
    {
        return array_map(
            Filestore::class . '::get',
            $this['files']
        );
    }
}
