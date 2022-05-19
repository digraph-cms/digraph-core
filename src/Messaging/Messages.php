<?php

namespace DigraphCMS\Messaging;

use DigraphCMS\DB\AbstractObjectManager;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\URL\URLs;

/**
 * @method static MessageSelect select()
 * @method static bool insert(Message $message)
 * @method static bool update(Message $message)
 * @method static bool delete(Message $message)
 */
class Messages extends AbstractObjectManager
{
    const TABLE = 'message';
    const INSERT_COLUMNS = [
        'uuid',
        'category',
        'subject',
        'sender',
        'recipient',
        'body',
        'time',
        'important',
        'sensitive',
        'email',
    ];
    const UPDATE_COLUMNS = [
        'read',
        'archived',
    ];
    const SELECT_CLASS = MessageSelect::class;

    public static function get(string $uuid): ?Message
    {
        return static::select()
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public static function send(Message $message)
    {
        // deliver message through in-site messaging
        $inserted = $message->insert();
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
                    Emails::send($email);
                }
            } else {
                // send non-important messages only to primary email
                $email = Email::newForUser(
                    $message->category(),
                    $message->recipient(),
                    $subject,
                    $body
                );
                if ($email) Emails::send($email);
            }
        }
        return $inserted;
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
