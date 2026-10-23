<h1>Delete filestore file</h1>
<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

Notifications::printWarning("Manually deleting filestore files here can cause errors elsewhere on the site. Please use this tool with caution and delete files through the rich media or other tools that they were originally added through if possible.");

$file = Filestore::get(Context::url()->actionSuffix());
if (!$file)
    throw new HttpError(404);

$form = new FormWrapper();
$form->button()->setText("Delete filestore file");

$form->addChild($file->card());

$confirm = (new CheckboxField("Yes, confirm deletion of the filestore file shown above. This action cannot be undone."))
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    if ($file->delete()) {
        Notifications::flashConfirmation("Filestore file deleted");
        throw new RedirectException(new URL('./'));
    }
    else {
        Notifications::flashError('Filestore file deletion failed');
        throw new RefreshException();
    }
}

echo $form;