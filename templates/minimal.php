<?php
/*
Minimal template page for use where the full UI is either overkill, or shouldn't
get so many resources allocated.
*/

use DigraphCMS\Context;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
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
    <?php
    echo Templates::render('sections/navbar.php');
    ?>
    <main id="page-wrapper">
        <?php
        echo '<div id="content">';
        Breadcrumb::print();
        echo new ActionMenu;
        Notifications::printSection();
        echo '<div id="article" class="page--' . Context::pageUUID() . '">';
        echo Context::response()->content();
        echo '</div>';
        echo '</div>';
        ?>
    </main>
    <?php
    echo Templates::render('sections/footer.php');
    ?>
</body>

</html>