<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use Flatrr\FlatArray;

/** @var FlatArray */
$wb = Context::fields()['wb_data'];
$clickableURL = 'http://' . $wb['original_url'];

?>

<h1>Wayback Machine</h1>

<p>
    The requested link has been automatically detected as a broken link.
    A potentially relevant snapshot of the contents of this URL has been found in the <a href="https://web.archive.org/" target="_blank" data-wayback-ignore="true">Wayback Machine</a>, a database of archived web pages founded by the <a href="https://archive.org/" target="_blank" data-wayback-ignore="true">Internet Archive</a>.
</p>

<p>
    <a href="<?php echo $wb['wb_url']; ?>" class="button button--confirmation" data-wayback-ignore="true">
        View archived copy of
        <code style='color:inherit;background:transparent;'><?php echo htmlspecialchars($wb['original_url']); ?></code>
    </a><br>
    <small>
        This snapshot was recorded <?php echo Format::date($wb['wb_time']); ?>.
    </small>
</p>

<p>
    You can also try <a href="<?php echo $clickableURL; ?>" target="_blank" data-wayback-ignore="true">visiting the original URL</a>, as this broken link detection is entirely automated and could have been triggered in error by temporary errors or server connectivity problems.
</p>