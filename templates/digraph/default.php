<?php
/*
This is the default template for pages that contain a full page of content, and
are not some sort of error or special case.
*/

use DigraphCMS\Context;
use DigraphCMS\UI\Actionbar;

$response = Context::response();
$fields = Context::fields();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $fields['page.name'] ?? 'Untitled'; ?> :: <?php echo $fields['site.name']; ?></title>
</head>

<body class='template-default'>
    <a href="#main-content" id="skip-to-content">Skip to content</a>
    <main>
        <?php echo new Actionbar(Context::url()); ?>
        <article id="main-content">
            <?php echo $response->content(); ?>
        </article>
    </main>
</body>

</html>