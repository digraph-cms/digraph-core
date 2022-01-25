<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;

$row = Context::fields()['row'];
$data = Context::fields()['data'];

$matchURL = $data['archived_snapshots']['closest']['url'];
$matchDate = DateTime::createFromFormat('YmdHis', $data['archived_snapshots']['closest']['timestamp']);

?>

<h1>Wayback Machine</h1>

<p>
    The requested link (<a href="<?php echo $row['url']; ?>" target="_blank"><?php echo $row['url']; ?></a>) has been detected as a broken link.
    A potential match for this link has been found in the <a href="https://web.archive.org/" target="_blank">Wayback Machine</a>, a database of archived internet data founded by the <a href="https://archive.org/" target="_blank">Internet Archive</a>.
</p>

<p>
    <a href="<?php echo $data['archived_snapshots']['closest']['url']; ?>" class="button button--confirmation">Click here to view archived copy</a><br>
    <small>
        This copy was recorded <?php echo Format::date($matchDate); ?>.<br>
        <?php 
        if ($row['date']) {
            echo "This is the copy nearest to the date this link was last updated on this site.";
        }else {
            echo "This is the most recent copy in the Wayback Machine.";
        }
        ?>
    </small>
</p>

<p>
    You can also try <a href="<?php echo $row['url']; ?>" target="_blank">visiting the original URL</a>, as this detection could be in error.
</p>