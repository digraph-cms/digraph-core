<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\UI\Breadcrumb;

// locate file
$page = Context::page();
$file = Filestore::select()
    ->like('parent', $page->uuid(), false, true)
    ->where('uuid', Context::url()->actionSuffix())
    ->fetch();
if (!$file) {
    foreach (RichMedia::select(Context::pageUUID()) as $media) {
        $file = Filestore::select()
            ->like('parent', $media->uuid(), false, true)
            ->where('uuid', Context::url()->actionSuffix())
            ->fetch();
    }
}

// check that it was found
if (!$file) throw new HttpError(404);
assert($file instanceof FilestoreFile);

// render file
Breadcrumb::setTopName($file->filename());
echo $file->card(display_meta: ['size', 'upload_date']);
