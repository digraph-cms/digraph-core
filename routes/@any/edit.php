<?php
$package->cache_noStore();

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
                ['name' => $form->object->link()]
            )
        );
        $package->redirect(
            $form->object->hook_postEditUrl()
        );
    } else {
        $cms->helper('notifications')->error(
            $cms->helper('strings')->string(
                'notifications.edit.error'
            )
        );
    }
}

echo $form;
