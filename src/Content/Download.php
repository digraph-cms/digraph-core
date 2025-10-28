<?php

namespace DigraphCMS\Content;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Context;
use DigraphCMS\DB\DBConnectionException;
use DigraphCMS\Digraph;
use DigraphCMS\FS;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\Media\File;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\User;
use Envms\FluentPDO\Exception;
use Stringable;
use ZipArchive;

class Download extends AbstractPage
{
    const ACTIONS_DISABLED = ['copy'];

    public function files(): FilestoreSelect
    {
        return Filestore::select()
            ->where('parent', $this->uuid() . '_dl');
    }

    public function setImmediateDownload(bool $immediate_download): self
    {
        if ($immediate_download) $this['immediate_download'] = true;
        else unset($this['immediate_download']);
        return $this;
    }

    public function immediateDownload(): bool
    {
        return $this['immediate_download'] ?? false;
    }

    public function card(): Stringable|string
    {
        $files = $this->files();
        if ($files->count() === 0) {
            return '<div class="file-card file-card--extension-unknown">No files found</div>';
        } elseif ($files->count() === 1) {
            return $files->fetch()->card();
        }
        $file = $this->zipFile();
        $card = (new DIV())
            ->addClass('file-card')
            ->addClass('multifile-card')
            ->addClass('card')
            ->addClass('file-card--extension-zip');
        // add card title
        $card->addChild((new DIV)
            ->addClass('card__title')
            ->addChild((new A)
                ->addChild($this->name())
                ->setAttribute('title', $file->filename())
                ->setAttribute('href', $file->url())));
        // add a list of individual files
        $id = 'multifile__list-' . $file->identifier();
        $wrapper = (new DIV)
            ->setID($id)
            ->addClass('navigation-frame navigation-frame--stateless')
            ->addClass('multifile-card__list');
        if (!Context::arg_string($id, true) == 'open') {
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
        return $card;
    }

    public function filesHash(): string
    {
        return Cache::get(
            'download/fileshash/' . $this->uuid() . $this->updated()->getTimestamp(),
            function () {
                $files = $this->files();
                $hashes = array_map(
                    function (FilestoreFile $f): string {
                        return $f->filename() . ',' . $f->hash();
                    },
                    $files->fetchAll()
                );
                return md5(implode(',', $hashes));
            },
            86400,
        );
    }

    /**
     * Get the file that will be downloaded by the user. Note that if there are no files uploaded, this will be an
     * empty zip file.
     * 
     * @return File 
     * @throws DBConnectionException 
     * @throws Exception 
     */
    public function download(): File
    {
        $count = $this->files()->count();
        if ($count === 1) {
            return $this->files()->fetch();
        } else {
            return $this->zipFile();
        }
    }

    public static function dl_permissions(string $uuid, User $user): bool
    {
        $page = Pages::get($uuid, static::class);
        if (!$page) return Permissions::inGroup('editors');
        return $page->permissions($page->url(), $user);
    }

    protected function zipFile(): DeferredFile
    {
        $page_uuid = $this->uuid();
        return new DeferredFile(
            $this->name() . '.zip',
            function (DeferredFile $file) {
                FS::mkdir(dirname($file->path()));
                $temp = preg_replace('/\.zip$/', '.' . Digraph::uuid() . '.zip', $file->path());
                $zip = new ZipArchive();
                $zip->open($temp, ZipArchive::CREATE);
                foreach ($this->files() as $f) {
                    $zip->addFile($f->path(), $f->filename());
                }
                $zip->close();
                FS::copy($temp, $file->path());
                unlink($temp);
            },
            $this->filesHash(),
            86400,
            function (User $user) use ($page_uuid): bool {
                return static::dl_permissions($page_uuid, $user);
            }
        );
    }
}