<?php
/*
This is the default template for pages that contain a full page of content, and
are not some sort of error or special case.
*/

use DigraphCMS\Context;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
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
    <?php echo Theme::head(); ?>
</head>

<body class='template-default'>
    <section id="skip-to-content">
        <a href="#content">Skip to content</a>
    </section>
    <?php
    echo new ActionMenu(Context::url(), true);
    Breadcrumb::print();
    Notifications::printSection();
    ?>
    <main id="content">
        <?php echo Context::response()->content(); ?>
    </main>
    <?php echo Theme::body(); ?>
</body>

</html>