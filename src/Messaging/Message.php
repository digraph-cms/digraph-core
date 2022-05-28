<?php

namespace DigraphCMS\Messaging;

use DateTime;
use DigraphCMS\DataObjects\DSOObject;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class Message extends DSOObject
{

    public function __toString()
    {
        return Templates::render('messaging/message-html.php', ['message' => $this]);
    }

    public function url()
    {
        return new URL('/~messages/msg_' . $this->uuid() . '.html');
    }

    public function insert(): bool
    {
        $this['time'] = time();
        return parent::insert();
    }

    public function subject(): string
    {
        return strip_tags($this['subject']);
    }

    public function setSubject(string $subject)
    {
        $this['subject'] = $subject;
        return $this;
    }

    public function sender(): User
    {
        return Users::user($this['sender']);
    }

    public function senderUUID(): string
    {
        return $this['sender'];
    }

    public function setSender(?User $sender)
    {
        $this['sender'] = $sender->uuid();
        return $this;
    }

    public function recipient(): User
    {
        return Users::user($this['recipient']);
    }

    public function recipientUUID(): string
    {
        return $this['recipient'];
    }

    public function setRecipient(User $recipient)
    {
        $this['recipient'] = $recipient->uuid();
        return $this;
    }

    public function body(): RichContent
    {
        return new RichContent($this['body']);
    }

    public function setBody(RichContent $body)
    {
        $this['body'] = $body->array();
        return $this;
    }

    public function time(): DateTime
    {
        return (new DateTime)->setTimestamp($this['time']);
    }

    public function setTime(DateTime $time)
    {
        $this['time'] = $time->getTimestamp();
    }

    public function read(): bool
    {
        return !!$this['read'];
    }

    public function setRead(bool $read)
    {
        $this['read'] = $read;
    }

    public function archived(): bool
    {
        return !!$this['archived'];
    }

    public function setArchived(bool $archived)
    {
        $this['archived'] = $archived;
    }
}
