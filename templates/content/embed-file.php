<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;

$file = Context::fields()['file'];

?>
<div class="embedded-file" data-extension="<?php echo $file->extension(); ?>">
    <h1>
        <a href="<?php echo $file->url(); ?>">
            <?php echo $file->filename(); ?>
        </a>
    </h1>
    <small>
        <div class='size'>
            <?php echo Format::filesize($file->bytes()); ?>
        </div>
        <span class='upload-date'>
            Uploaded <?php echo Format::date($file->created()); ?>
        </span>
        <span class='uploader'>
            by <?php echo $file->createdBy(); ?>
        </span>
        <div class='md5'>
            MD5 <?php echo $file->hash(); ?>
        </div>
    </small>
</div>