<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Theme;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
use DigraphCMS\URL\URL;

Context::response()->template('chromeless.php');
Theme::addBlockingPageCss('/rich_media_editor/*.css');

$media = RichMedia::get(Context::arg('uuid'));
if (!$media) throw new HttpError(400);

echo '<div id="rich-media-deleter">';
echo '<div id="rich-media-deleter__content">';

echo '<h1>Delete <em>' . $media->name() . '</em>?</h1>';
echo '<p>This action cannot be undone.</p>';

echo '<div style="display:flex;">';

$edit = new URL('../editor/');
$edit->arg('frame', Context::arg('frame'));
$edit->arg('uuid', Context::arg('uuid'));
echo '<a href="' . $edit . '" class="button button--neutral">Back to editing</a>';

echo new ToolbarSpacer;

echo (new CallbackLink(function () use ($media) {
    $media->delete();
    echo "<p>Attempting to close window.</p>";
    echo <<<SCRIPT
    <script>
    // refresh frame in opener
    const frame = (new URLSearchParams(location.search)).get('frame');
    if (frame && window.opener) window.opener.document.getElementById(frame).reloadFrame();
    // close this window
    window.close();
    </script>
    SCRIPT;
    exit();
}))
    ->addChild('Delete')
    ->addClass('button button--danger');

echo '</div>';

echo '</div>';
echo '</div>';
