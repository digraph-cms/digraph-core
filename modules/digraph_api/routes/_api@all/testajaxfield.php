<?php
$fh = $cms->helper('forms');
$form = new Formward\Form('test field');

$form['noun'] = $fh->field('noun', 'Test noun search');

echo $form;
var_dump($form->value());
