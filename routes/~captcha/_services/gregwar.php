<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Security\Security;
use DigraphCMS\URL\URL;
use Gregwar\Captcha\CaptchaBuilder;

if (!isset($_SESSION['gregwar_captcha_image']) || Context::arg('refresh')) {
    $builder = new CaptchaBuilder();
    $builder->build();
    $_SESSION['gregwar_captcha_image'] = '<img src="' . $builder->inline() . '" alt="CAPTCHA" />';
    $_SESSION['gregwar_captcha_phrase'] = $builder->getPhrase();
}

$form = new FormWrapper('gregwar-captcha-form--' . md5(Context::url()));
$form->setCaptcha(false);
$form->button()->setText('Submit CAPTCHA');

$form->addChild(sprintf(
    '<div class="navigation-frame navigation-frame--stateless" id="gregwar-captcha" data=target="_frame">%s<br>%s</div>',
    $_SESSION['gregwar_captcha_image'],
    '<a href="' . new URL('&refresh=1') . '">refresh</a>'
));

$phrase = (new Field('Enter the text shown above'))
    ->addValidator(function (InputInterface $input) {
        if (strtolower($input->value()) != strtolower($_SESSION['gregwar_captcha_phrase'])) {
            return 'Incorrect CAPTCHA phrase';
        }
        return null;
    })
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    Security::unflag();
    unset($_SESSION['gregwar_captcha_image']);
    unset($_SESSION['gregwar_captcha_phrase']);
    throw new RefreshException();
}

echo $form;
