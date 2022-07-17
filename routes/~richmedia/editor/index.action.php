<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Theme;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
use DigraphCMS\URL\URL;

Context::response()->template('chromeless.php');
Theme::addBlockingPageCss('/rich_media_editor/*.css');
Theme::addBlockingPageJs('/rich_media_editor/*.js');

// open wrapper
echo '<div id="rich-media-editor">';

// buttons
echo '<div id="rich-media-editor__buttons">';
// delete button
if (!Context::arg('add')) {
    $delete = new URL('../delete/');
    $delete->arg('frame', Context::arg('frame'));
    $delete->arg('uuid', Context::arg('uuid'));
    echo "<a href='$delete' id='data--delete' class='button button--danger'>Delete</a>";
}
// spacer
echo new ToolbarSpacer;
// cancel button
echo (new CallbackLink(function () {
    // TODO: this is where you could run cleanups of half-completed items
    echo "Attempting to close window.<script>window.close()</script>";
    exit();
}))
    ->addChild(Context::arg('add') ? 'Cancel' : 'Close')
    ->setID('button--close')
    ->addClass('button button--warning');
echo '</div>';

// interface div
echo '<div id="rich-media-editor__interface">';

// creation form
if (Context::arg('add')) {
    // ensure we have a valid UUID for the new media
    Context::ensureUUIDArg(RichMedia::class);
    // set up new object
    $class = Config::get('rich_media_types.' . Context::arg('add'));
    /** @var AbstractRichMedia */
    $media = new $class();
    $media->setUUID(Context::arg('uuid'));
    $media->setParent(Context::arg('parent'));
    // get form from object
    $form = $media->editForm(true);
    // set button name
    $form->button()->setText('Create ' . $media::className());
    // extra handler to make form redirect to edit form
    $form->addCallback(function () {
        $url = Context::url();
        $url->unsetArg('add');
        $url->unsetArg('parent');
        throw new RedirectException($url);
    });
}
// editing form
else {
    $media = RichMedia::get(Context::arg('uuid'));
    if (!$media) throw new HttpError(400);
    // get form from object
    $form = $media->editForm();
    // set button name
    $form->button()->setText('Save changes');
    // callback to force a refresh to avoid double-submits and allow refreshing without resubmit prompts
    $form->addCallback(function () {
        throw new RefreshException();
    });
}

// display form
echo $form;

// closing divs
echo '</div>';
echo '</div>';
