<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

$url = new URL('/~search/');
$value = htmlentities(strip_tags(Context::arg('q') ?? ''));

?>
<form class="search-form" method="get" action="<?php echo $url; ?>">
    <input placeholder="Search this site" class="search-form__query" value="<?php echo $value; ?>" name="q">
    <input class="submit-button" type="submit" value="search">
</form>