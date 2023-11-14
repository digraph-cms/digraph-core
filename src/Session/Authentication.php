<?php

namespace DigraphCMS\Session;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class Authentication
{
    protected $id, $user, $userUUID, $comment, $secret, $created, $expires, $ip, $ua;

    public function __construct(array $row)
    {
        $this->id = $row['id'];
        $this->user = Users::user($row['user_uuid']);
        $this->userUUID = $row['user_uuid'];
        $this->comment = $row['comment'];
        $this->secret = $row['secret'];
        $this->created = (new DateTime)->setTimestamp((int)$row['created']);
        $this->expires = (new DateTime)->setTimestamp((int)$row['expires']);
        $this->ip = $row['ip'];
        $this->ua = $row['ua'];
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userUUID(): string
    {
        return $this->userUUID;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function comment(): string
    {
        return $this->comment;
    }

    public function created(): DateTime
    {
        return clone $this->created;
    }

    public function expires(): DateTime
    {
        return clone $this->expires;
    }

    public function ip(): string
    {
        return $this->ip;
    }

    public function ua(): string
    {
        return $this->ua;
    }

    public function update(): bool
    {
        return DB::query()->update(
            'session',
            [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'ua' => $_SERVER['HTTP_USER_AGENT']
            ],
            $this->id
        )->execute();
    }

    public function deauthenticate(string $message)
    {
        DB::query()
            ->insertInto(
                'session_expiration',
                [
                    'session_id' => $this->id,
                    'date' => time(),
                    'reason' => $message
                ]
            )
            ->execute();
    }
}
