<?php
/*
This template is designed for use in places where performance and cleanliness of
presentation are important, such as less serious error pages. It should include
site-specific styling, but may omit navigation and extraneous design elements in
the name of speed and efficiency for users.
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

<body class='template-minimal'>
    <a href="#main-content" id="skip-to-content">Skip to content</a>
    <?php echo new Actionbar(Context::url()); ?>
    <main>
        <article id="main-content">
            <?php echo $response->content(); ?>
        </article>
    </main>
</body>

</html>