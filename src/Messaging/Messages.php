<?php

namespace DigraphCMS\Messaging;

use DigraphCMS\DataObjects\AbstractStaticDSOFactory;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Users\User;

/**
 * @method static MessageQuery query()
 */
class Messages extends AbstractStaticDSOFactory
{
    protected static function name(): string
    {
        return 'messages';
    }

    public static function create(User $recipient, string $subject, RichContent $body): Message
    {
        /** @var Message */
        $message = static::factory()->create([
            'sender' => 'system',
            'archived' => false,
            'read' => false
        ]);
        $message->setRecipient($recipient);
        $message->setSubject($subject);
        $message->setBody($body);
        return $message;
    }

    /**
     * Query to return unread inbox messages, used for notification dot and
     * user menu interface
     *
     * @return MessageQuery
     */
    public static function notify(): MessageQuery
    {
        return static::query()
            ->inbox()
            ->unread();
    }
}
