<?php

use DigraphCMS\Context;
use DigraphCMS\Search\SearchResult;

/** @var SearchResult */
$result = Context::fields()['result'];

?>
<div class="search-results__item">
    <div class="search-results__title">
        <a href="<?php echo $result->url(); ?>">
            <?php echo $result->title(); ?><a>
    </div>
    <div class="search-results__body"><?php echo $result->snippet(); ?></div>
    <div class="search-results__url"><?php echo $result->url(); ?></div>
</div>