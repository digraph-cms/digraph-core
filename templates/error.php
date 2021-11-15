<?php
/*
This template is designed for use on serious error pages, and vigorously avoids 
calling any unnecessary outside resources. No extra database calls, linked media 
files or anything else that might make an error message take up any more
resources than absolutely necessary.
*/

use DigraphCMS\Context;
use DigraphCMS\Media\Media;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo Context::fields()['page.name'] ?? 'Untitled'; ?>
        :: <?php echo Context::fields()['site.name']; ?>
    </title>
    <style>
        <?php echo Media::get('/core/error_blocking.css')->content(); ?>
    </style>
</head>

<body class='template-error'>
    <?php echo Context::response()->content(); ?>
</body>

</html>