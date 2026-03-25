<?php

use DigraphCMS\Content\Download;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\CheckboxList;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadMulti;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\User;

$page = Context::page();
assert($page instanceof Download);

$form = new FormWrapper('edit-' . $page->uuid());

$name = (new Field('Download name'))
    ->setDefault($page->name(null, true))
    ->setRequired(true)
    ->addTip('The name to be used when referring or linking to this download from elsewhere on the site.')
    ->addForm($form);

$files_deleter = (new CheckboxList());
(new Field('Files to delete', $files_deleter))
    ->addTip('Check any files you would like to remove from this download.')
    ->addForm($form);
foreach ($page->files() as $file) {
    assert($file instanceof FilestoreFile);
    $files_deleter->addOption($file->uuid(), $file->filename());
}

$files = (new Field('Upload file(s)', $upload = new UploadMulti()))
    ->addTip('Select one or more additional files to upload.')
    ->addTip('If you add multiple files, they will be combined into a single zip file for download')
    ->addForm($form);

$immediate_download = (new CheckboxField('Download immediately'))
    ->setDefault($page->immediateDownload())
    ->addTip('Check this box to make the main URL of this page immediately download the files to non-editors')
    ->addForm($form);

$content = (new RichContentField('Body content', $page->uuid()))
    ->setDefault($page->richContent('body'))
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    DB::beginTransaction();
    // update page
    $page->name($name->value());
    $page->setImmediateDownload($immediate_download->value());
    $page->richContent('body', $content->value());
    $page->update();
    // delete files
    foreach ($files_deleter->value() as $uuid => $deleted) {
        if (!$deleted)
            continue;
        $file = \DigraphCMS\Content\Filestore::get($uuid);
        $file->delete();
    }
    // upload new files
    $page_uuid = $page->uuid();
    $upload->filestore(
        $page->uuid() . '_dl',
        function (User $user) use ($page_uuid): bool {
            return Download::dl_permissions($page_uuid, $user);
        }
    );
    // commit all and bounce
    DB::commit();
    Notifications::flashConfirmationHTML('Download updated: ' . $page->url()->html());
    throw new RedirectException($page->url());
}

echo $form;
