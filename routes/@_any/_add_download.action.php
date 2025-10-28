<?php

use DigraphCMS\Content\Download;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadMulti;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\User;

Context::ensureUUIDArg(Pages::class);

$page = Context::page();

$form = new FormWrapper('add-' . Context::arg_string('uuid'));

$name = (new Field('Download name'))
    ->setRequired(true)
    ->addTip('The name to be used when referring or linking to this download from elsewhere on the site.')
    ->addForm($form);

$files = (new Field('File(s)', $upload = new UploadMulti()))
    ->setRequired(true)
    ->addTip('Select one or more files to upload.')
    ->addTip('If you select multiple files, they will be combined into a single zip file for download')
    ->addForm($form);

$immediate_download = (new CheckboxField('Download immediately'))
    ->addTip('Check this box to make the main URL of this page immediately download the files to non-editors')
    ->addForm($form);

$content = (new RichContentField('Body content', Context::arg_string('uuid')))
    ->setDefault('# [page_name]' . PHP_EOL . PHP_EOL . '[download_card]')
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    DB::beginTransaction();
    // insert page
    $page = new Download();
    $page->setUUID(Context::arg_string('uuid'));
    $page->name($name->value());
    $page->setImmediateDownload($immediate_download->value());
    $page->richContent('body', $content->value());
    $page->insert(Context::page()->uuid());
    // upload files to the filestore
    $page_uuid = $page->uuid();
    $upload->filestore(
        $page->uuid() . '_dl',
        function (User $user) use ($page_uuid): bool {
            return Download::dl_permissions($page_uuid, $user);
        }
    );
    // commit all and bounce
    DB::commit();
    Notifications::flashConfirmation('Download created: ' . $page->url()->html());
    throw new RedirectException($page->url_edit());
}

echo $form;