<h1>Filestore files</h1>
<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Content\Pages;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnUserFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;

$files = Filestore::select()
    ->order('created desc');

$table = new PaginatedTable(
    $files,
    function (FilestoreFile $file): array {
        $page = null;
        $media = null;
        if ($page = Pages::get($file->parentUUID())) {
            $media = null;
        } elseif ($media = RichMedia::get($file->parentUUID())) {
            $page = Pages::get($media->parentUUID());
        }
        return [
            sprintf(
                '<a href="%s">%s</a>',
                $file->url(),
                $file->filename()
            ),
            $file->parent(),
            $page ? $page->url()->html() : '',
            $media ? $media->name() : '',
            Format::filesize($file->bytes()),
            Format::date($file->created()),
            $file->createdBy()
        ];
    },
    [
        new ColumnStringFilteringHeader('Filename', 'filename'),
        new ColumnStringFilteringHeader('Parent string', 'parent'),
        'Page',
        'Rich media',
        new ColumnSortingHeader('Size', 'bytes'),
        new ColumnDateFilteringHeader('Created', 'created'),
        new ColumnUserFilteringHeader('Created by', 'created_by'),
    ]
);

echo $table;