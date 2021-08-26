<?php
/*
This is a template that includes the bare minimum UI. Notifications and content.
*/

use DigraphCMS\Context;
use DigraphCMS\Media\Media;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Theme;

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
        <?php echo Media::get('/digraph/error.css')->content(); ?>
    </style>
    <?php echo Theme::head(); ?>
</head>

<body class='template-minimal'>
    <?php
    Notifications::printSection();
    ?>
    <main id="content">
        <?php echo Context::response()->content(); ?>
    </main>
    <?php echo Theme::body(); ?>
</body>

</html>