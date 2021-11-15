<?php
/*
Minimal template page for use where the full UI is either overkill, or shouldn't
get so many resources allocated.
*/

use DigraphCMS\Context;
use DigraphCMS\UI\Breadcrumb;
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

<body class='template-minimal'>
    <?php
    echo new UserMenu(Context::url());
    if (Context::response()->status() == 200) {
        Breadcrumb::print();
    }
    ?>
    <main id="content">
        <?php echo Context::response()->content(); ?>
    </main>
    <?php
    echo Templates::render('sections/footer.php');
    ?>
</body>

</html>