<?php
/*
Minimal template page for use where the full UI is either overkill, or shouldn't
get so many resources allocated.
*/

use DigraphCMS\Context;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\UI\Theme;
use DigraphCMS\UI\UserMenu;

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
    echo new UserMenu();
    echo Templates::render('sections/header.php');
    echo Templates::render('sections/navbar.php');
    ?>
    <main id="content">
        <?php
        Notifications::printSection();
        if (Context::response()->status() == 200) {
            Breadcrumb::print();
        }
        echo '<div id="main-content">';
        echo Context::response()->content();
        echo '</div>';
        ?>
    </main>
    <?php
    echo Templates::render('sections/footer.php');
    ?>
</body>

</html>