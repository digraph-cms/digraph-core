<?php
/*
This is the default template for pages that contain a full page of content, and
are not some sort of error or special case.
*/

use DigraphCMS\Context;
use DigraphCMS\Cron\Cron;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Sidebar\Sidebar;
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

<body class='template-default no-js <?php echo implode(' ', Theme::bodyClasses()); ?>'>
    <section id="skip-to-content">
        <a href="#content">Skip to content</a>
    </section>
    <?php
    Cookies::printConsentBanner();
    echo new UserMenu;
    echo Templates::render('sections/header.php');
    echo Templates::render('sections/navbar.php');
    ?>
    <main id="page-wrapper">
        <?php
        echo '<div id="content">';
        Breadcrumb::print();
        echo new ActionMenu;
        Notifications::printSection();
        echo '<div id="article">';
        echo Context::response()->content();
        echo '</div>';
        echo '</div>';
        echo Sidebar::render();
        ?>
    </main>
    <?php
    echo Templates::render('sections/footer.php');
    echo Cron::renderPoorMansCron();
    ?>
</body>

</html>