<?php

use DigraphCMS\Context;
use DigraphCMS\Forms\Forms;
use DigraphCMS\HTTP\Redirect;

$form = Forms::pageForm(Context::page(), 'edit');
if ($form->handle()) {
    return new Redirect(Context::page()->url());
}
echo $form;
