<?php
/*
This is the default template for pages that contain a full page of content, and
are not some sort of error or special case.
*/

use DigraphCMS\Context;
use DigraphCMS\UI\ActionMenu;
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

<body class='template-default'>
    <section id="skip-to-content">
        <a href="#content">Skip to content</a>
    </section>
    <section id="header">
        <?php echo Templates::render('sections/header.php'); ?>
    </section>
    <nav id="navbar">
        <?php echo Templates::render('sections/navbar.php'); ?>
    </nav>
    <?php
    Breadcrumb::print();
    Notifications::printSection();
    ?>
    <main id="content">
        <?php echo Context::response()->content(); ?>
    </main>
    <?php
    echo new UserMenu(Context::url());
    echo new ActionMenu(Context::url(), false);
    ?>
    <section id="footer">
        <?php echo Templates::render('sections/footer.php'); ?>
    </section>
    <?php
    echo Theme::body();
    ?>
</body>

</html>