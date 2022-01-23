<?php

namespace DigraphCMS\Session;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class PHPAuthentication extends Authentication
{
    public function __construct(array $row)
    {
        @session_start();
        $_SESSION[Config::get('php_session.key')] = [
            'id' => $row['id'] ?? random_int(1, PHP_INT_MAX),
            'user_uuid' => $row['user_uuid'],
            'comment' => $row['comment'],
            'secret' => $row['secret'],
            'created' => (int)$row['created'],
            'expires' => (int)$row['expires'],
            'ip' => $row['ip'],
            'ua' => $row['ua']
        ];
    }

    public function id(): string
    {
        return $_SESSION[Config::get('php_session.key')]['id'];
    }

    public function userUUID(): string {
        return $_SESSION[Config::get('php_session.key')]['user_uuid'] ?? 'guest';
    }

    public function user(): User
    {
        if ($user = $_SESSION[Config::get('php_session.key')]['user_uuid']) {
            return Users::get($user);
        } else {
            return Users::guest();
        }
    }

    public function comment(): string
    {
        return $_SESSION[Config::get('php_session.key')]['comment'];
    }

    public function created(): DateTime
    {
        return (new DateTime())
            ->setTimestamp(
                $_SESSION[Config::get('php_session.key')]['created']
            );
    }

    public function expires(): DateTime
    {
        return (new DateTime())
            ->setTimestamp(
                $_SESSION[Config::get('php_session.key')]['expires']
            );
    }

    public function ip(): string
    {
        return $_SESSION[Config::get('php_session.key')]['ip'];
    }

    public function ua(): string
    {
        return $_SESSION[Config::get('php_session.key')]['ua'];
    }

    public function deauthenticate(string $message)
    {
        unset($_SESSION[Config::get('php_session.key')]);
    }
}
