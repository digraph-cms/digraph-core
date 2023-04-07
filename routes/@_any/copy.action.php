<?php

use DigraphCMS\Content\Graph;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageField;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;

$form = new FormWrapper('copy-' . Context::pageUUID());
$form->button()->setText('Copy page');

$parent = (new PageField('Parent'))
    ->setDefault(
        Context::page()->parent()
            ? (Context::page()->parentPage() ? Context::page()->parentPage()->uuid() : null)
            : null
    )
    ->setRequired(false)
    ->addTip('You can clear this field to create a detached copy of the page that has no parent.')
    ->addForm($form);

$slug = (new Field('URL pattern'))
    ->setDefault(Context::page()->slugPattern())
    ->setRequired(true)
    ->addTip('You have the option to alter the page\'s URL pattern now, and it and all children will be created with this new pattern as their root.')
    ->addTip('Add a leading slash to make pattern relative to site root, otherwise it will be relative to the page\'s parent URL.')
    ->addForm($form);

$name = (new Field('Name'))
    ->setDefault(Context::page()->name())
    ->setRequired(true)
    ->addTip('You also have the option to change the page\s name now, so that it will be created with the given name at the moment it is created.')
    ->addForm($form);

$recurse = (new CheckboxField('Copy all children recursively'))
    ->addTip('Check this box to also copy all children of this page recursively.')
    ->addTip('This process may take a long time if there are a large number of pages below this one.')
    ->addTip('If the children of this page are linked to each other in cycles or other complex non-tree graphs, those will not be copied and the copies will only include the links found in a breadth-first search.')
    ->addForm($form);

$clone = (new CheckboxField('Create new copies of rich media'))
    ->addTip('By default rich media is not copied, and the new page will continue to reference the rich media attached to the original pages.')
    ->addTip('Check this box to attempt to create new copies of any rich media in the copied pages and update their content with the new UUIDs of the copies.')
    ->addTip('This process is slower, and may not successfully update all references to the copied media.')
    ->addForm($form);

// add validator to avoid copying a page into itself
$parent->addValidator(function()use($parent,$recurse){
    if (!$recurse->value()) return null;
    if (Graph::route(Context::pageUUID(),$parent->value())) return 'Cannot recursively copy a page below itself. Either disable recursive copy option, or set a parent that is not a descendant of the page to be copied.';
    return null;
});

$form->addCallback(function () use ($parent, $slug, $name, $recurse, $clone) {
    $new = Context::page()->copy(
        Pages::get($parent->value()),
        $slug->value(),
        $name->value(),
        $recurse->value(),
        $clone->value()
    );
    throw new RedirectException($new->url('_copy_log.html'));
});

echo $form;
