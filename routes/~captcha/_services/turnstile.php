<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Curl\CurlHelper;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Security\CaptchaMisconfigurationException;
use DigraphCMS\Security\Security;
use DigraphCMS\UI\Notifications;

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

// hide form
$form->setStyle('display', 'none');

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
<noscript>
    <?php Notifications::printError("Javascript is required to complete this verification."); ?>
</noscript>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=<?= $callback_id ?>" defer></script>
<script>
    window.<?= $callback_id ?> = function() {
        turnstile.render('#<?= $container_id ?>', {
            sitekey: '<?= Config::get('captcha.turnstile.site_key') ?>',
            // TODO: capture some sort of information in "action" key to give better analytics
            callback: function(token) {
                document.getElementById('<?= $token->id() ?>').value = token;
                Digraph.submitForm(document.getElementById('<?= $form->id() ?>').getElementsByTagName('form')[0]);
            },
        });
    };
</script>
<div class="turnstile-interface" id="<?= $container_id ?>"></div>
<?= $form ?>