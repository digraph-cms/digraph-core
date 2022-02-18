<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageField;
use DigraphCMS\HTML\Forms\Fields\RadioListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\RichMedia;

$media = RichMedia::get(Context::arg('edit'));

$form = new FormWrapper('edit-rich-media-' . Context::arg('edit') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Save changes');

$name = (new Field('Bookmark name'))
    ->setDefault($media->name())
    ->setRequired(true)
    ->addTip('Used as the default link text for this bookmark');

$mode = (new RadioListField('What type of bookmark would you like to create?', [
    'url' => 'Link to any URL on the web',
    'page' => 'Bookmark a page on this site'
]))
    ->setRequired(true)
    ->setDefault($media['mode']);

$url = (new Field('URL'))
    ->setID('bookmark-url-field')
    ->setDefault($media['url'] ?? '');
$url->input()
    ->setAttribute('placeholder', 'https://')
    ->addValidator(function () use ($url) {
        if ($url->value() && !filter_var($url->value(), FILTER_VALIDATE_URL)) {
            return "Please enter a valid URL";
        }
        return null;
    });

$page = (new PageField('Page'))
    ->setDefault($media['page'] ?? '')
    ->setID('bookmark-page-field');

echo $form
    ->addChild($name)
    ->addChild($mode)
    ->addChild($url)
    ->addChild($page)
    ->addCallback(function () use ($media, $name, $mode, $url, $page) {
        // set up name
        $media->name($name->value());
        // save other fields
        $media['mode'] = $mode->value();
        $media['url'] = $url->value();
        $media['page'] = $page->value();
        // update and redirect
        $media->update();
        $url = Context::url();
        $url->unsetArg('edit');
        $url->arg('_tab_tab', 'page');
        throw new RedirectException($url);
    });

?>
<script>
    (() => {
        // get elements
        var url = document.getElementById('<?php echo $mode->field('url')->input()->id(); ?>');
        var page = document.getElementById('<?php echo $mode->field('page')->input()->id(); ?>');
        var url_field = document.getElementById('<?php echo $url->id(); ?>');
        var page_field = document.getElementById('<?php echo $page->id(); ?>');
        // add event listeners
        url.addEventListener('change', checkStatus);
        page.addEventListener('change', checkStatus);
        // do initial check
        checkStatus();
        // status checking 
        function checkStatus() {
            url_field.style.display = url.checked ? null : 'none';
            page_field.style.display = page.checked ? null : 'none';
        }
    })();
</script>