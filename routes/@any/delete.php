<?php
$package['response.cacheable'] = false;
$form = new Formward\Form('Really delete this page?');
$form->submitButton()->label('Yes, really delete it');

if ($form->handle()) {
    $package->noun()->delete();
    $package->cms()->helper('notifications')->confirm('Page deleted successfully');
} else {
    echo $form;
}
