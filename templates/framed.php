<?php
/*
Minimal template page for use in navigation frames.
*/

use DigraphCMS\Context;
use DigraphCMS\UI\Notifications;

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
</head>

<body class='template-framed'>
    <?php Notifications::printSection(); ?>
    <main id="content">
        <div id="main-content">
            <?php echo Context::response()->content(); ?>
        </div>
    </main>
</body>

</html>