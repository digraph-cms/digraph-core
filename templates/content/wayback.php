<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;

/** @var DigraphCMS\URL\WaybackResult */
$wb = Context::fields()['wb'];
$clickableURL = 'http://'.$wb->originalURL();

?>

<h1>Wayback Machine</h1>

<p>
    The requested link (<a href="<?php echo $clickableURL; ?>" target="_blank"><?php echo $wb->originalURL(); ?></a>) has been automatically detected as a broken link.
    A potential match for this link has been found in the <a href="https://web.archive.org/" target="_blank">Wayback Machine</a>, a database of archived internet data founded by the <a href="https://archive.org/" target="_blank">Internet Archive</a>.
</p>

<p>
    <a href="<?php echo $wb->wbURL(); ?>" class="button button--confirmation">Click here to view archived copy</a><br>
    <small>
        This snapshot was recorded <?php echo Format::date($wb->wbTime()); ?>.
    </small>
</p>

<p>
    You can also try <a href="<?php echo $clickableURL; ?>" target="_blank">visiting the original URL</a>, as this broken link detection is entirely automated and could have been triggered in error by temporary errors or connectivity problems.
</p>