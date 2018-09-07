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

$form->handle(function (&$form) use ($package,$noun) {
    foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_handled.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_add_handled.php') as $file) {
        include $file['file'];
    }
});
if ($form->handle()) {
    $package->redirect($form->object->url()->string());
}

echo $form;
