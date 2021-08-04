<?php
/*
This template is designed for use on serious error pages, and vigorously avoids 
calling any unnecessary outside resources. No extra database calls, linked media 
files or anything else that might make an error message take up any more
resources than absolutely necessary.
*/

use DigraphCMS\Context;

$response = Context::response();
$fields = Context::fields();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $fields['page.name'] ?? 'Error'; ?> :: <?php echo $fields['site.name']; ?></title>
</head>

<body class='template-error'>
    <?php echo $response->content(); ?>
</body>

</html>