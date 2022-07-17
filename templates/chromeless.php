<?php
/*
Template with absolutely no chrome outside notifications and body content
largely used for things like popup UI windows, rich media editors, and the like
*/

use DigraphCMS\Context;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Theme;

Theme::addInternalPageCss('/styles_chromeless/*.css');

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

<body class='template-chromeless no-js <?php echo implode(' ', Theme::bodyClasses()); ?>'>
    <?php
    Notifications::printSection();
    echo '<div id="article" class="page--' . Context::pageUUID() . '">';
    echo Context::response()->content();
    echo '</div>';
    ?>
</body>

</html>