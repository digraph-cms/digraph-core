<?php

namespace DigraphCMS\Session;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

final class Authentication
{
    protected $id, $user, $comment, $secret, $created, $expires, $ip, $ua;

    public function __construct(array $row)
    {
        $this->id = $row['id'];
        $this->user = Users::user($row['user']);
        $this->comment = $row['comment'];
        $this->secret = $row['secret'];
        $this->created = new DateTime($row['created']);
        $this->expires = new DateTime($row['created']);
        $this->ip = $row['ip'];
        $this->ua = $row['ua'];
    }

    public function id(): string
    {
        return $this->id;
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

    public function deauthenticate(string $message)
    {
        DB::query()
            ->insertInto(
                'sess_exp',
                [
                    'auth' => $this->id,
                    'date' => date("Y-m-d H:i:s"),
                    'reason' => $message
                ]
            )
            ->execute();
    }
}
