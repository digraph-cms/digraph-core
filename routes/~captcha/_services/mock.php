<?php

use DigraphCMS\Captcha\Captcha;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;

$form = new FormWrapper();

$checkbox = (new CheckboxField('I am not a robot'))
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    Captcha::unflag();
    throw new RefreshException();
}

echo $form;
