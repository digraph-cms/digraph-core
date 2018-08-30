<?php
$package['response.cacheable'] = false;
$noun = $package->noun();
$forms = $this->helper('forms');
$form = $forms->editNoun($noun);

foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_edit.php') as $file) {
    include $file['file'];
}

if ($form->handle()) {
    $package->cms()->invalidateCache($form->noun['dso.id']);
    $package->redirect($form->noun->url()->string());
    foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_handled.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_edit_handled.php') as $file) {
        include $file['file'];
    }
    return;
}

echo $form;
