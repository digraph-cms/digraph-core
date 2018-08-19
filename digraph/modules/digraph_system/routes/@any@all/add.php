<?php
$forms = $this->helper('forms');
$form = $forms->addNoun($this->package->url()['noun']);

foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_add.php') as $file) {
    include $file['file'];
}

if ($form->handle()) {
    $this->package->redirect($form->noun->url()->string());
    foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_handled.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_add_handled.php') as $file) {
        include $file['file'];
    }
    return;
}

echo $form;
