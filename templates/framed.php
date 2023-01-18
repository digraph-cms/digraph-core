<?php
/*
Minimal template page for use in navigation frames.
*/

use DigraphCMS\Context;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Sidebar\Sidebar;

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
</head>

<body class='template-framed'>
    <div style="display:none;">
        <?php
        Breadcrumb::print();
        ?>
    </div>
    <main id="page-wrapper">
        <?php
        echo '<div id="content">';
        Breadcrumb::print();
        Notifications::printSection();
        echo '<div id="article" class="page--' . Context::pageUUID() . '">';
        echo Context::response()->content();
        echo '</div>';
        echo '</div>';
        echo Sidebar::render();
        ?>
    </main>
</body>

</html>