<!-- media-editor-force-wide -->
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\RadioListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TableInput;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\Types\TableRichMedia;

$form = new FormWrapper('add-rich-media-' . Context::arg('add') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Add media');

$name = (new Field('Media name'))
    ->setRequired(true)
    ->addTip('Used only to identify this table in the media browser, and not displayed to users');

$toggle = (new RadioListField('How would you like to enter the table\'s content?', [
    'edit' => 'Edit table content manually',
    'file' => 'Upload a spreadsheet'
]))
    ->setRequired(true)
    ->setDefault('edit');

$table = (new Field('Table content', new TableInput()))
    ->setID('add-table-edit-field');

$file = (new Field('Upload file', new UploadSingle()))
    ->setID('add-table-file-field')
    ->addTip('First row will be used as headers');

$form
    ->addChild($name)
    ->addChild($toggle)
    ->addChild($table)
    ->addChild($file)
    ->addCallback(function () use ($name, $toggle, $table, $file) {
        // set up new media and its file
        $media = new TableRichMedia([], ['page_uuid' => Context::arg('uuid')]);
        // set up name
        $media->name($name->value());
        // insert and redirect
        $media->insert();
        $url = Context::url();
        $url->unsetArg('add');
        $url->arg('_tab_tab', 'page');
        throw new RedirectException($url);
    });

echo $form;

?>
<script>
    (() => {
        // get elements
        var edit = document.getElementById('<?php echo $toggle->field('edit')->input()->id(); ?>');
        var file = document.getElementById('<?php echo $toggle->field('file')->input()->id(); ?>');
        var edit_field = document.getElementById('<?php echo $table->id(); ?>');
        var file_field = document.getElementById('<?php echo $file->id(); ?>');
        // add event listeners
        edit.addEventListener('change', checkStatus);
        file.addEventListener('change', checkStatus);
        // do initial check
        checkStatus();
        // status checking 
        function checkStatus() {
            edit_field.style.display = edit.checked ? null : 'none';
            file_field.style.display = file.checked ? null : 'none';
        }
    })();
</script>