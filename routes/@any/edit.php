<?php
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;

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
    $cms->helper('notifications')->flashConfirmation(
        $cms->helper('strings')->string('notifications.edit.confirmation', ['name'=>$form->object->name()])
    );
    $package->redirect($form->object->url()->string());
}

echo $form;
