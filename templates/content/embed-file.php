<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;

$filestore = Context::fields()['file'];

?>
<div class="embedded-file" data-extension="<?php echo $filestore->extension(); ?>">
    <h1>
        <a href="<?php echo $filestore->url(); ?>">
            <?php echo $filestore->filename(); ?>
        </a>
    </h1>
    <small>
        <div class='size'>
            <?php echo Format::filesize($filestore->bytes()); ?>
        </div>
        <span class='upload-date'>
            Uploaded <?php echo Format::date($filestore->created()); ?>
        </span>
        <span class='uploader'>
            by <?php echo $filestore->createdBy(); ?>
        </span>
        <div class='md5'>
            MD5 <?php echo $filestore->hash(); ?>
        </div>
    </small>
</div>