<h1>Replace filestore file</h1>
<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

Notifications::printWarning("Manually replacing filestore files here can cause errors elsewhere on the site. Please use this tool with caution and edit files through the rich media or other tools that they were originally added through if possible.");

$file = Filestore::get(Context::url()->actionSuffix());
if (!$file)
    throw new HttpError(404);

$form = new FormWrapper();
$form->button()->setText("Replace filestore file");

$form->addChild($file->card());

$upload_field = (new Field("Replacement file", $upload = new UploadSingle()))
    ->setRequired(true);

$confirm = (new CheckboxField("Yes, confirm replacement of the filestore file shown above. This action cannot be undone."))
    ->setRequired(true);

if ($form->ready()) {
    $new_file = $upload->filestore($file->uuid(), $file->permissions());
}

echo $form;