<?php
/*
Minimal template page for use where the full UI is either overkill, or shouldn't
get so many resources allocated.
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

<body class='template-minimal'>
    <main id="content">
        <?php echo Context::response()->content(); ?>
    </main>
    <?php echo Theme::body(); ?>
</body>

</html>