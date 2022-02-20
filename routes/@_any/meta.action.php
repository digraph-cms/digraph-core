<?php

use DigraphCMS\Content\Slugs;
use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;

$page = Context::page();

?>
<table>
    <tr>
        <th>Created</th>
        <td>
            <?php echo Format::datetime($page->created()); ?>
            by <?php echo $page->createdBy(); ?>
        </td>
    </tr>
    <tr>
        <th>Last modified</th>
        <td>
            <?php echo Format::datetime($page->updated()); ?>
            by <?php echo $page->updatedBy(); ?>
        </td>
    </tr>
    <tr>
        <th>Type</th>
        <td>
            <?php echo $page->class(); ?>
            (<?php echo get_class($page); ?>)
        </td>
    </tr>
    <tr>
        <th>UUID</th>
        <td>
            <a href="<?php echo $page->url('', [], true); ?>">
                <?php echo $page->uuid(); ?>
            </a>
        </td>
    </tr>
    <tr>
        <th>URLs</th>
        <td>
            <ul>
                <?php
                foreach (Slugs::list($page->uuid()) as $slug) {
                    $url = new URL("/$slug/");
                    echo "<li><a href='$url'>$slug</a></li>";
                }
                ?>
            </ul>
        </td>
    </tr>
</table>