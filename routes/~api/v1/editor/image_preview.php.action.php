<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;

if ($file = Filestore::get(Context::arg('image'))) {
    if ($image = $file->image()) {
        Context::response()->redirect(
            $image->webp()->optimize()->width(650)->url()
        );
        return;
    }
}

throw new HttpError(404);
