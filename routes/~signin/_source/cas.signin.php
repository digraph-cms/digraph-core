<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;

// display individual provider
$provider = Context::arg('_provider');
$config = Config::get("user_sources.cas.providers.$provider");
echo "<h1>" . $config['name'] . "</h1>";

if (!@$config['mock_cas_user']) {
    // BEGIN CONFIGURING CAS
    // load class so constants exist
    class_exists('phpCAS');
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
    phpCAS::client(
        CAS_VERSION_2_0,
        $config['server'],
        intval($config['port']),
        $config['context']
    );

    //set up configured config calls
    if (@$config['setnocasservervalidation']) {
        phpCAS::setNoCasServerValidation();
    }

    // TRY TO SIGN IN
    if (!phpCAS::isAuthenticated()) {
        phpCAS::forceAuthentication();
    }
    Context::data('signin_provider_id', phpCAS::getUser());
} else {
    // USE MOCK CAS USER
    if (!Context::arg('_mockcasuser')) {
        $form = new FormWrapper('mock-cas-user');
        $username = new Field('Username');
        $username->setRequired(true);
        $form->addChild($username);
        $form->addCallback(function () use ($username) {
            $url = clone Context::url();
            $url->arg('_mockcasuser', $username->value());
            throw new RedirectException($url);
        });
        echo $form;
    } else {
        Context::data('signin_provider_id', Context::arg('_mockcasuser'));
    }
}
