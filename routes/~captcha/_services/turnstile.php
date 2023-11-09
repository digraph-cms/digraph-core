<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Security\CaptchaMisconfigurationException;
use DigraphCMS\Security\Security;

if (!Config::get('captcha.turnstile.site_key')) {
    throw new CaptchaMisconfigurationException('Turnstile site key not configured (Config: captcha.turnstile.site_key)');
}
if (!Config::get('captcha.turnstile.secret_key')) {
    throw new CaptchaMisconfigurationException('Turnstile secret key not configured (Config: captcha.turnstile.secret_key)');
}

$id = md5(Context::url());
$container_id = 'turnstile--' . $id;
$callback_id = 'turnstile' . $id;

$form = new FormWrapper('form--' . $id);
$form->setCaptcha(false);

// add an input for the token
$token = new INPUT('token');
$form->addChild($token);

// hide token and form
$token->setAttribute('type','hidden');
$form->setStyle('display','none');

echo $form;

if ($form->ready()) {
    $response = CurlHelper::post(
        'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        [
            'secret' => Config::get('captcha.turnstile.secret_key'),
            'response' => $token->value(),
        ]
    );
    if ($response) {
        $response = json_decode($response, true);
        if ($response['success']) {
            Security::unflag();
        } else {
            Security::flag('Failed turnstile CAPTCHA');
        }
    }
    throw new RefreshException();
}

?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=<?= $callback_id ?>" defer></script>
<script>
    window.<?= $callback_id ?> = function() {
        turnstile.render('#<?= $container_id ?>', {
            sitekey: '<?= Config::get('captcha.turnstile.site_key') ?>',
            callback: function(token) {
                document.getElementById('<?= $token->id() ?>').value = token;
                document.getElementById('<?= $form->form()->id() ?>').submit();
            },
        });
    };
</script>
<div id="<?= $container_id ?>"></div>