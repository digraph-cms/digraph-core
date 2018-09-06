<?php
$package['response.cacheable'] = false;

$type = $package['url.args.type'];
$forms = $this->helper('forms');
$form = $forms->addNoun($type);

if (!$package['url.args.home']) {
    $this->helper('notifications')->notice();
} else {
    $form['slug'] = new Formward\Fields\Hidden('Slug');
    $form['slug']->value('home');
}

foreach ($this->helper('routing')->allHookFiles($type, 'form.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles($type, 'form_add.php') as $file) {
    include $file['file'];
}

$form->handle(function (&$form) use ($package,$type) {
    foreach ($this->helper('routing')->allHookFiles($type, 'form_handled.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles($type, 'form_add_handled.php') as $file) {
        include $file['file'];
    }
});
if ($form->handle()) {
    // $package->redirect($form->noun->url()->string());
}

echo $form;
