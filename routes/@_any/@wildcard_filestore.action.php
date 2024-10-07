<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\UI\Breadcrumb;

// locate file
$file = Filestore::get(Context::url()->actionSuffix());
if (!$file) throw new HttpError(404);
assert($file instanceof FilestoreFile);

// check that its parent belongs at the current location
$page = null;
$media = null;
if ($page = Pages::get($file->parentUUID())) {
    $media = null;
} elseif ($media = RichMedia::get($file->parentUUID())) {
    $page = Pages::get($media->parentUUID());
}
if (!$page || $page->uuid() != Context::pageUUID()) throw new HttpError(404);

// render file
Breadcrumb::setTopName($file->filename());
echo $file->card(display_meta: ['size', 'upload_date']);
