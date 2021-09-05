<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;

$page = Context::page();

?>
<h1>Page metadata</h1>
<table>
    <tr>
        <th>Created</th>
        <td>
            <?php echo Format::datetime($page->created()->format('Y-m-d H:i:s')); ?>
            by <?php echo $page->createdBy(); ?>
        </td>
    </tr>
    <tr>
        <th>Last modified</th>
        <td>
            <?php echo Format::datetime($page->updated()->format('Y-m-d H:i:s')); ?>
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