<h1>Add URL redirect</h1>
<?php
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\Redirects;
use DigraphCMS\URL\URL;

echo '<div class="navigation-frame navigation-frame--stateless" id="url-redirect-interface">';

$form = new FormWrapper();
$form->setData('target', '_frame');
$form->button()->setText('Add redirect');

$from = (new Field('Redirect from'))
    ->setRequired(true)
    ->addForm($form)
    ->addValidator(function (InputInterface $input) {
        if (!$input->value()) return null;
        try {
            Context::beginUrlContext(new URL('/'));
            new URL($input->value());
            Context::end();
            return null;
        } catch (Throwable $th) {
            Context::end();
            if ($th instanceof Exception) return 'Parsing URL failed: ' . $th->getMessage();
            else return 'Parsing URL failed';
        }
    });

$to = (new Field(label: 'Redirect to'))
    ->setRequired(true)
    ->addForm($form)
    ->addValidator(function (InputInterface $input) {
        if (!$input->value()) return null;
        try {
            Context::beginUrlContext(new URL('/'));
            new URL($input->value());
            Context::end();
            return null;
        } catch (Throwable $th) {
            Context::end();
            if ($th instanceof Exception) return 'Parsing URL failed: ' . $th->getMessage();
            else return 'Parsing URL failed';
        }
    });

if ($form->ready()) {
    Context::beginUrlContext(new URL('/'));
    Redirects::create(
        new URL($from->value()),
        new URL($to->value())
    );
    Context::end();
    throw new RedirectException(new URL('./'));
}
echo $form;

echo '</div>';