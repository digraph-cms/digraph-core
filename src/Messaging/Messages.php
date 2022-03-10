<?php

namespace DigraphCMS\Messaging;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\Email\Email;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Users;

class Messages
{
    /**
     * Generate a select object for building queries
     *
     * @return MessageSelect
     */
    public static function select(): MessageSelect
    {
        return new MessageSelect(
            DB::query()->from('message')
        );
    }

    public static function get(string $uuid): ?Message
    {
        return static::select()
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public static function resultToMessage(array $row): Message
    {
        $message = new Message(
            $row['subject'],
            Users::user($row['recipient']),
            new RichContent(json_decode($row['body'], true)),
            $row['category'],
            $row['uuid']
        );
        $message->setSender($row['sender'] ? Users::user($row['sender']) : null);
        $message->setTime((new DateTime)->setTimestamp($row['time']));
        $message->setRead(!!$row['read']);
        $message->setArchived(!!$row['archived']);
        $message->setImportant(!!$row['important']);
        $message->setSensitive(!!$row['sensitive']);
        $message->setEmail(!!$row['email']);
        return $message;
    }

    public static function send(Message $message)
    {
        // deliver message through in-site messaging
        DB::query()->insertInto(
            'message',
            [
                'uuid' => $message->uuid(),
                'category' => $message->category(),
                'subject' => $message->subject(),
                'sender' => $message->senderUUID(),
                'recipient' => $message->recipientUUID(),
                'body' => json_encode($message->body()->array()),
                'time' => $message->time()->getTimestamp(),
                'important' => $message->important() ? 1 : 0,
                'sensitive' => $message->sensitive() ? 1 : 0,
                'email' => $message->email() ? 1 : 0
            ]
        )->execute();
        // deliver by email
        if ($message->email()) {
            if ($message->sensitive()) {
                // sensitive messages only email a link to view them, so that users must sign in
                $subject = "Message received";
                $body = new RichContent(
                    sprintf(
                        "You received a secure message on %s<br><a href='%s'>View it online</a>",
                        URLs::site(),
                        $message->url()
                    )
                );
            } else {
                // non-sensitive messages include the whole message subject and body
                $subject = $message->subject();
                $body = new RichContent(
                    sprintf(
                        "You received a message on %s<br><a href='%s'>View it online</a>",
                        URLs::site(),
                        $message->url()
                    )
                        . PHP_EOL . PHP_EOL
                        . '<hr>'
                        . PHP_EOL . PHP_EOL
                        . $message->body()->html()
                );
            }
            if ($message->important()) {
                // send important messages to all emails
                $emails = Email::newForUser_all(
                    $message->category(),
                    $message->recipient(),
                    $subject,
                    $body
                );
                foreach ($emails as $email) {
                    $email->send();
                }
            } else {
                // send non-important messages only to primary email
                $email = Email::newForUser(
                    $message->category(),
                    $message->recipient(),
                    $subject,
                    $body
                );
                if ($email) $email->send();
            }
        }
    }

    public static function update(Message $message)
    {
        DB::query()->update('message')
            ->where('uuid = ?', [$message->uuid()])
            ->set(
                [
                    'read' => $message->read() ? 1 : 0,
                    'archived' => $message->archived() ? 1 : 0
                ]
            )
            ->execute();
    }

    /**
     * Query to return unread inbox messages, used for notification dot and
     * user menu interface
     *
     * @return MessageSelect
     */
    public static function notify(): MessageSelect
    {
        return static::select()
            ->inbox()
            ->unread();
    }
}
