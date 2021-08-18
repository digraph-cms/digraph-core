<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\URL\URL;

$page = Context::page();

?>
<table>
    <tr>
        <td>Created</td>
        <td>
            <?php echo $page->created()->format('Y-m-d H:i:s') ?>
            by <?php echo $page->createdBy()->html(); ?>
        </td>
    </tr>
    <tr>
        <td>Last modified</td>
        <td>
            <?php echo $page->updated()->format('Y-m-d H:i:s') ?>
            by <?php echo $page->updatedBy()->html(); ?>
        </td>
    </tr>
    <tr>
        <td>Type</td>
        <td>
            <?php echo $page->class(); ?>
            (<?php echo get_class($page); ?>)
        </td>
    </tr>
    <tr>
        <td>UUID</td>
        <td>
            <a href="<?php echo $page->url('', [], true); ?>">
                <?php echo $page->uuid(); ?>
            </a>
        </td>
    </tr>
    <tr>
        <td>URLs</td>
        <td>
            <ul>
                <li><strong>
                        <a href="<?php echo $page->url('', [], false); ?>">
                            <?php echo $page->slug(); ?>
                        </a>
                        (primary)
                    </strong></li>
                <?php
                foreach (Pages::alternateSlugs($page->uuid()) as $slug) {
                    $url = new URL("/$slug/");
                    echo "<li><a href='$url'>$slug</a></li>";
                }
                ?>
            </ul>
        </td>
    </tr>
</table>