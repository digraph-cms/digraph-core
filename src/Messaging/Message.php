<?php

namespace DigraphCMS\Messaging;

use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;

class Message
{
    protected $uuid, $subject, $sender, $recipient, $body, $time, $category;
    protected $read = false;
    protected $archived = false;
    protected $important = false;
    protected $sensitive = false;
    protected $email = false;

    public function __construct(string $subject, User $recipient, RichContent $body, string $category, string $uuid = null)
    {
        $this->subject = $subject;
        $this->recipient = $recipient;
        $this->body = $body;
        $this->uuid = $uuid ?? Digraph::uuid();
        $this->category = $category;
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

    public function sender(): ?User
    {
        return $this->sender;
    }

    public function senderUUID(): ?string
    {
        return $this->sender() ? $this->sender()->uuid() : null;
    }

    public function setSender(?User $sender)
    {
        $this->sender = $sender;
    }

    public function recipient(): User
    {
        return $this->recipient;
    }

    public function recipientUUID(): string
    {
        return $this->recipient()->uuid();
    }

    public function setRecipient(User $recipient)
    {
        $this->recipient = $recipient;
    }

    public function body(): RichContent
    {
        return $this->body;
    }

    public function setBody(RichContent $body)
    {
        $this->body = $body;
    }

    public function time(): ?DateTime
    {
        return $this->time ? clone $this->time : null;
    }

    public function setTime(DateTime $time)
    {
        $this->time = clone $time;
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
