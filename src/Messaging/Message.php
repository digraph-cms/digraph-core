<?php

namespace DigraphCMS\Messaging;

use DateTime;
use DigraphCMS\DB\AbstractManagedObject;
use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class Message extends AbstractManagedObject
{
    const OBJECT_MANAGER = Messages::class;
    protected $id;
    protected $uuid;
    protected $category;
    protected $subject;
    protected $sender;
    protected $recipient;
    protected $body;
    protected $time;
    protected $read = false;
    protected $archived = false;
    protected $important = false;
    protected $sensitive = false;
    protected $email = false;

    public function __construct(string $subject = null, User $recipient = null, RichContent $body = null, string $category = null)
    {
        $this->subject = $this->subject ?? $subject;
        $this->recipient = $this->recipient ?? $recipient->uuid();
        $this->body = $this->body ?? json_encode(($body ?? new RichContent(''))->array());
        $this->category = $this->category ?? $category ?? 'messaging';
        $this->uuid = $this->uuid ?? Digraph::uuid('msg');
        $this->sender = $this->sender ?? 'system';
        $this->time = $this->time ?? time();
    }

    public function __toString()
    {
        return Templates::render('messaging/message-html.php', ['message' => $this]);
    }

    public function url()
    {
        return new URL('/~messages/' . $this->uuid() . '.html');
    }

    public function send()
    {
        $this->time = new DateTime;
        Messages::send($this);
    }

    public function update()
    {
        Messages::update($this);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function subject(): string
    {
        return strip_tags($this->subject);
    }

    public function sender(): User
    {
        return Users::user($this->sender);
    }

    public function senderUUID(): string
    {
        return $this->sender;
    }

    public function setSender(?User $sender)
    {
        $this->sender = $sender;
    }

    public function recipient(): User
    {
        return Users::user($this->recipient);
    }

    public function recipientUUID(): string
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient)
    {
        $this->recipient = $recipient->uuid();
    }

    public function body(): RichContent
    {
        return new RichContent(json_decode($this->body, true));
    }

    public function setBody(RichContent $body)
    {
        $this->body = json_encode($body->array());
    }

    public function time(): DateTime
    {
        return (new DateTime)->setTimestamp($this->time);
    }

    public function setTime(DateTime $time)
    {
        $this->time = $time->getTimestamp();
    }

    public function read(): bool
    {
        return $this->read;
    }

    public function setRead(bool $read)
    {
        $this->read = $read;
    }

    public function archived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived)
    {
        $this->archived = $archived;
    }

    public function important(): bool
    {
        return $this->important;
    }

    public function setImportant(bool $important)
    {
        $this->important = $important;
    }

    public function sensitive(): bool
    {
        return $this->sensitive;
    }

    public function setSensitive(bool $sensitive)
    {
        $this->sensitive = $sensitive;
    }

    public function email(): bool
    {
        return $this->email;
    }

    public function setEmail(bool $email)
    {
        $this->email = $email;
    }

    public function category(): string
    {
        return $this->category;
    }
}
