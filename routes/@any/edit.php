<?php
$package->noCache();

$noun = $package->noun();
if (!$noun->isEditable()) {
    $package->error(401);
    return;
}

$forms = $this->helper('forms');
$form = $forms->editNoun($noun);

foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_edit.php') as $file) {
    include $file['file'];
}

if ($form->handle()) {
    foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_handled.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles($noun['dso.type'], 'form_edit_handled.php') as $file) {
        include $file['file'];
    }
    if ($form->object->update()) {
        $cms->helper('notifications')->flashConfirmation(
            $cms->helper('strings')->string(
                'notifications.edit.confirmation',
                ['name'=>$form->object->link()]
            )
        );
    } else {
        $cms->helper('notifications')->flashError(
            $cms->helper('strings')->string(
                'notifications.edit.error'
            )
        );
    }
    $package->redirect($form->object->url('edit', null, true)->string());
}

echo $form;
