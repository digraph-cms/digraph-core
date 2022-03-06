<?php

namespace DigraphCMS\Messaging;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\RichContent\RichContent;
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
        // TODO: deliver by email if requested and allowed by user
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
