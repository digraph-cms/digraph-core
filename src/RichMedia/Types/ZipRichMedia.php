<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\FS;
use DigraphCMS\HTML\A;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\OrderingInput;
use DigraphCMS\HTML\Forms\UploadMulti;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use ZipArchive;

class ZipRichMedia extends AbstractRichMedia
{
    protected $zipFile;

    public function prepareForm(FormWrapper $form, $create = false)
    {
        // name input
        $name = (new Field('Name'))
            ->setRequired(true)
            ->setDefault($this->name())
            ->addForm($form);

        // existing files
        if ($this->files()) {
            $order = (new Field('Current files', new OrderingInput()))
                ->addForm($form)
                ->addTip('Drag and drop to reorder in web lists (order in downloaded zip file can\'t be controlled');
            foreach ($this->files() as $file) {
                $order->input()->addLabel($file->uuid(), $file->filename());
            }
            $order->setDefault($this['files']);
            $order->input()->setAllowDeletion(true);
        } else $order = null;

        // upload field
        $files = (new Field($create ? 'File' : 'Add files', new UploadMulti()))
            ->addForm($form);
        if ($create) $files->setRequired(true);

        // options
        $options = (new CheckboxListField(
            'Options',
            [
                'single' => 'Allow listing and downloading individual files'
            ]
        ))
            ->setDefault($this['options'] ?? [])
            ->addForm($form);

        // meta
        $meta = (new CheckboxListField(
            'Display metadata',
            [
                'uploader' => 'Update user',
                'upload_date' => 'Update date',
            ]
        ))
            ->setDefault($this['meta'] ?? [])
            ->addForm($form);

        // callback for taking in values
        $form->addCallback(function () use ($name, $order, $files, $options, $meta) {
            // set name
            $this->name($name->value());
            // set options
            unset($this['options']);
            $this['options'] = $options->value();
            // set meta
            unset($this['meta']);
            $this['meta'] = $meta->value();
            if ($order) {
                // delete files
                $deleted = array_diff($this['files'], $order->value());
                foreach ($deleted as $f) {
                    Filestore::get($f)->delete();
                }
                // set remaining file order
                unset($this['files']);
                $this['files'] = $order->value();
            }
            // add new files to list
            $this['files'] = array_merge(
                $this['files'] ?? [],
                array_map(
                    function (FilestoreFile $file): string {
                        return $file->uuid();
                    },
                    $files->input()->filestore($this->uuid())
                )
            );
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
        if ($code->getParameter('inline') || $code->getContent()) {
            return (new A)
                ->setAttribute('href', $media->zipFile()->url())
                ->setAttribute('title', $media->zipFile()->filename())
                ->addChild($code->getContent() ?? $media->zipFile()->filename());
        } else {
            return $media->card();
        }
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
            $this['files'] ?? []
        );
    }
}
