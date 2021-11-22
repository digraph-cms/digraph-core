<?php

namespace DigraphCMS\UI\DataLists;

use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Content\FilestoreSelect;

class FileList extends AbstractPaginatedList
{
    protected $query, $callback;

    public function __construct(FilestoreSelect $query, callable $callback = null)
    {
        parent::__construct($query->count());
        $this->query = $query;
        $this->callback = $callback ?? [$this, 'renderItem'];
    }

    public function class(): string
    {
        return 'data-list file-list';
    }

    public function items(): array
    {
        static $items;
        if ($items === null) {
            $items = [];
            $this->query->offset($this->paginator->startItem());
            $this->query->limit($this->paginator->perPage());
            while ($item = $this->query->fetch()) {
                $items[] = ($this->callback)($item, $this);
            }
        }
        return $items;
    }

    protected function renderItem(FilestoreFile $file): string
    {
        if ($image = $file->image()) {
            return sprintf(
                '<img src="%s">',
                $image->fit($image::FIT_CROP, 100, 100)->url()
            );
        } else {
            return sprintf(
                '<a href="%s">%s</a>',
                $file->url(),
                $file->filename()
            );
        }
    }
}
