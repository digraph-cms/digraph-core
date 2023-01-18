<?php
/*
Fallback page template that is fault tolerant and designed for use when unknown
errors need to be handled.
*/

use DigraphCMS\Context;
use DigraphCMS\Media\Media;
use DigraphCMS\UI\Templates;
use DigraphCMS\UI\Theme;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php echo Templates::render('sections/analytics.php'); ?>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo Context::fields()['page.name'] ?? 'Untitled'; ?>
        :: <?php echo Context::fields()['site.name']; ?>
    </title>
    <?php echo Theme::renderVariableCss(); ?>
    <style>
        <?php echo Media::get('/styles_fallback/error_blocking.css')->content(); ?>
    </style>
    <?php
    try {
        echo Theme::head();
    } catch (\Throwable $th) {
        //throw $th;
    }
    ?>
</head>

<body class='template-fallback no-js'>
    <?php
    try {
        echo Templates::render('sections/navbar.php');
    } catch (\Throwable $th) {
        //throw $th;
    }
    echo '<main id="page-wrapper">';
    echo '<div id="content">';
    echo '<div id="article" class="page--' . Context::pageUUID() . '">';
    echo Context::response()->content();
    echo '</div>';
    echo '</div>';
    echo '</main>';
    try {
        echo Templates::render('sections/footer.php');
    } catch (\Throwable $th) {
        //throw $th;
    }
    ?>
    </main>
</body>

</html>