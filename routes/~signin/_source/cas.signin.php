<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Formward\Fields\Input;

// display individual provider
$provider = Context::arg('_provider');
$config = Config::get("user_sources.cas.providers.$provider");
echo "<h1>" . $config['name'] . "</h1>";

if (!@$config['mock_cas_user']) {
    // BEGIN CONFIGURING CAS
    // load class so constants exist
    class_exists('\phpCAS');
    // fudge $_SERVER values if you're in an environment where CAS can't tell you
    // have HTTPS enabled, such as a proxy server handling SSL
    if (@$config['fixhttpsproblems']) {
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['HTTPS'] = 'on';
    }

    //set up client, initialize phpCAS
    switch (@$config['version']) {
        case 'CAS_VERSION_1_0':
            $version = CAS_VERSION_1_0;
            break;
        case 'CAS_VERSION_2_0':
            $version = CAS_VERSION_2_0;
            break;
        case 'CAS_VERSION_3_0':
            $version = CAS_VERSION_3_0;
            break;
        default:
            $version = CAS_VERSION_2_0;
    }
    \phpCAS::client(
        CAS_VERSION_2_0,
        $config['server'],
        intval($config['port']),
        $config['context']
    );

    //set up configured config calls
    if (@$config['setnocasservervalidation']) {
        \phpCAS::setNoCasServerValidation();
    }

    // TRY TO SIGN IN
    Context::data('signin_provider_id', \phpCAS::getUser());
} else {
    // USE MOCK CAS USER
    if (!Context::arg('_mockcasuser')) {
        $form = new Form('Debug tool: Enter your mock CAS username');
        $form['username'] = new Input('Username');
        $form['username']->required(true);
        if ($form->handle()) {
            $url = clone Context::url();
            $url->arg('_mockcasuser', $form['username']->value());
            throw new RedirectException($url);
        }
        echo $form;
    } else {
        Context::data('signin_provider_id', Context::arg('_mockcasuser'));
    }
}
