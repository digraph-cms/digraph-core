<?php

use DigraphCMS\Content\Blocks\Blocks;
use DigraphCMS\Content\Blocks\FileBlock;
use DigraphCMS\Content\Filestore;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\URL\URL;
use Formward\Fields\File;
use Formward\Fields\Input;

$block = Blocks::get(Context::arg('block'));
if (!$block || !($block instanceof FileBlock)) {
    throw new HttpError(404);
}

Context::response()->template('iframe.php');
$mainURL = new URL('/~blocks/page.php');
$mainURL->query([
    'editor' => Context::arg('editor'),
    'page' => Context::arg('page')
]);

$form = new Form('Edit file download block');

$form['caption'] = new Input('Caption');
$form['caption']->addTip('Optional. Leave blank to use the filename as the caption.');
$form['caption']->default($block->name());

$form['file'] = new File('File');
$form['file']->addTip('Optional. Only necessary to replace the existing file.');

if ($form->handle()) {

    DB::beginTransaction();

    $block->name($form['caption']->value());

    if ($form['file']->value()) {
        // delete old file
        Filestore::get($block->file())->delete();
        // upload and set new file
        $file = Filestore::upload(
            $form['file']->value()['file'],
            $form['file']->value()['name'],
            Context::arg('page'),
            []
        );
        $block['file'] = $file->uuid();
    }

    $block->update();
    DB::commit();

    throw new RedirectException($mainURL);
}

echo $form;

echo new SingleButton('Cancel adding block', function () use ($mainURL) {
    throw new RedirectException($mainURL);
}, ['error']);
