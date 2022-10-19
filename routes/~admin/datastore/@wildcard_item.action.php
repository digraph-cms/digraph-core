<?php

use DigraphCMS\CodeMirror\CodeMirrorField;
use DigraphCMS\CodeMirror\YamlArrayInput;
use DigraphCMS\Context;
use DigraphCMS\Datastore\Datastore;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

$item = Datastore::getByID(Context::url()->actionSuffix());

Breadcrumb::parents(
    [
        new URL('namespace:' . $item->namespaceName()),
        (new URL('namespace:' . $item->namespaceName() . '?grp=' . $item->groupName()))->setName('Group ' . $item->groupName())
    ]
);
Breadcrumb::setTopName($item->key());

// display metadata
echo "<h1>" . $item->groupName() . "/" . $item->key() . "</h1>";
echo new PaginatedTable([
    ['Created', sprintf('%s by %s', Format::date($item->created()), $item->createdBy())],
    ['Updated', sprintf('%s by %s', Format::date($item->updated()), $item->updatedBy())]
]);

// form for editing
$form = new FormWrapper();
$form->button()->setText('Save changes');

$value = (new Field('Value'))
    ->setDefault($item->value())
    ->addForm($form);

$data = (new Field('Data', new YamlArrayInput))
    ->addTip('Displayed as YAML for editing purposes, but stored as JSON internally')
    ->setDefault($item->data())
    ->addForm($form);

if ($form->ready()) {
    $item->setValue($value->value())
        ->setData($data->value())
        ->update();
    throw new RefreshException();
}

echo $form;

// delete button
$delete = (new CallbackLink(function () use ($item) {
    $item->delete();
    Notifications::flashConfirmation('Deleted datastore item');
    throw new RedirectException(new URL('namespace:' . $item->namespaceName() . '?grp=' . $item->groupName()));
}))->addChild('Delete item')->addClass('button button--danger');
echo '<hr>';
echo $delete;
