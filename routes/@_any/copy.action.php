<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageField;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\UI\Notifications;

$form = new FormWrapper('copy-' . Context::pageUUID());
$form->button()->setText('Copy page');

$parent = (new PageField('Parent'))
    ->setDefault(
        Context::page()->parent()
            ? (Context::page()->parentPage() ? Context::page()->parentPage()->uuid() : null)
            : null
    )
    ->setRequired(true);
$form->addChild($parent);

$name = (new Field('Name'))
    ->setDefault(Context::page()->name() . ' copy')
    ->setRequired(true);
$form->addChild($name);

$media = RichMedia::select(Context::pageUUID());
$clones = [];
if ($media->count() && Context::page()->allRichContent()) {
    $group = new FIELDSET('Clone rich media');
    $group->addChild('<p><small>By default rich media is not copied, and the new page will continue to reference the rich media attached to the original page. Check any rich media you would like to clone a copy of for the new page instead. The system will attempt to automatically update any embed tags to point to the new cloned media, but you should double check them.</small></p>');
    foreach ($media as $m) {
        $group->addChild(
            $clones[$m->uuid()] = new CheckboxField($m->name())
        );
    }
    $form->addChild($group);
}

$form->addCallback(function () use ($clones, $parent, $name) {
    DB::beginTransaction();
    // copy page
    $newPage = clone Context::page();
    $newPage->setUUID(Digraph::uuid());
    $newPage->name($name->value());
    $newPage['copied_from'] = Context::pageUUID();
    // clone media
    $clonedMedia = [];
    foreach ($clones as $uuid => $field) {
        if ($field->value()) {
            $clonedMedia[$uuid] = clone RichMedia::get($uuid);
            $clonedMedia[$uuid]->setUUID(Digraph::uuid());
            $clonedMedia[$uuid]->setPageUUID($newPage->uuid());
            $clonedMedia[$uuid]->insert();
        }
    }
    // recursively set data replacing media UUIDs
    if ($clonedMedia) {
        $fn = function (&$ar) use ($clonedMedia, &$fn) {
            foreach ($ar as $k => $v) {
                if (is_string($v)) {
                    foreach ($clonedMedia as $oldUUID => $newMedia) {
                        $ar[$k] = str_replace($oldUUID, $newMedia->uuid(), $v);
                    }
                } elseif (is_array($v)) {
                    $fn($ar[$k]);
                }
            }
        };
        $data = $newPage->get();
        $fn($data);
        $newPage->set(null, $data);
    }
    // create link to parent
    Pages::insertLink($parent->value(), $newPage->uuid());
    // insert new page
    $newPage->insert();
    // commit database updates
    DB::commit();
    // bounce to edit page for new page
    $newPage = Pages::get($newPage->uuid());
    Notifications::flashConfirmation(sprintf(
        'Copied %s from %s',
        $newPage->url()->html(),
        Pages::get($parent->value())->url()->html()
    ));
    throw new RedirectException($newPage->url_edit());
});

echo $form;
