<?php
/*
Captcha-specific template. Extremely minimal UI and no client-side analytics.
*/

use DigraphCMS\Context;
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
    <?php echo Theme::head(); ?>
</head>

<body class='template-minimal no-js <?php echo implode(' ', Theme::bodyClasses()); ?>'>
<main id="page-wrapper">
    <?php
    echo '<div id="content">';
    echo '<div id="article" class="page--' . Context::pageUUID() . '">';
    echo Context::response()->content();
    echo '</div>';
    echo '</div>';
    ?>
</main>
</body>

</html>