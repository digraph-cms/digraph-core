<?php
$type = $this->package->url()['noun'];
$forms = $this->helper('forms');
$form = $forms->addNoun($type);

foreach ($this->helper('routing')->allHookFiles($type, 'form.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles($type, 'form_add.php') as $file) {
    include $file['file'];
}

if ($form->handle()) {
    $this->package->redirect($form->noun->url()->string());
    foreach ($this->helper('routing')->allHookFiles($type, 'form_handled.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles($type, 'form_add_handled.php') as $file) {
        include $file['file'];
    }
    return;
}

echo $form;
