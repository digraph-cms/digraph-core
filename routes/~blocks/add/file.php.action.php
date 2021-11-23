<?php

use DigraphCMS\Content\Blocks\FileBlock;
use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\URL\URL;
use Formward\Fields\File;
use Formward\Fields\Input;

Context::response()->template('iframe.php');
$mainURL = new URL('/~blocks/page.php');
$mainURL->query([
    'editor' => Context::arg('editor'),
    'page' => Context::arg('page')
]);

$form = new Form('Add file download block');
$form['caption'] = new Input('Caption');
$form['caption']->addTip('Optional. Leave blank to use the filename as the caption.');
$form['file'] = new File('File');
$form['file']->required(true);
if ($form->handle()) {
    DB::beginTransaction();
    $file = Filestore::upload(
        $form['file']->value()['file'],
        $form['file']->value()['name'],
        Context::arg('page'),
        []
    );
    $block = new FileBlock(
        [
            'caption' => $form['caption'],
            'file' => $file->uuid()
        ],
        [
            'name' => $form['caption']->value() ? $form['caption']->value() : $form['file']->value()['name'],
            'page_uuid' => Context::arg('page')
        ]
    );
    $block->insert();
    DB::commit();
    throw new RedirectException($mainURL);
}
echo $form;
echo new SingleButton('Cancel adding block', function () use ($mainURL) {
    throw new RedirectException($mainURL);
}, ['error']);
