<h1>Add user group</h1>
<?php

use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$form = new FormWrapper;

$name = (new Field('Group name'))
    ->setRequired(true)
    ->addForm($form);

$uuid = (new Field('Internal ID (leave blank to auto-generate)'))
    ->addValidator(function (InputInterface $input) {
        if (!$input->value()) return null;
        if (preg_match('/[^A-Za-z0-9_]/', $input->value())) return 'Invalid ID, alphanumeric characters and underscores only';
        return null;
    })
    ->addForm($form);

if ($form->ready()) {
    try {
        DB::query()->insertInto('user_group', [
            'uuid' => $uuid->value() ? $uuid->value() : Digraph::uuid(),
            'name' => strip_tags($name->value())
        ])->execute();
        Notifications::flashConfirmation('Group added');
        throw new RedirectException(new URL('./'));
    } catch (\Exception $th) {
        if ($th instanceof RedirectException) throw $th;
        Notifications::error('Failed to add group: ' . $th->getMessage());
    }
}

echo $form;
