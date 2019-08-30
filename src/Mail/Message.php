<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mail;

class Message
{
    protected $to = [];
    protected $from = 'noreply@fake.domain';
    protected $cc = [];
    protected $bcc = [];
    protected $subject = 'Untitled email';
    protected $body = '';
    protected $replyTo = null;
    protected $sendAfter = 0;
    protected $tags = [];

    public function tags() : array
    {
        return $this->tags;
    }

    public function addTag(string $tag)
    {
        $this->tags[] = $tag;
        $this->tags = array_unique($this->tags);
    }

    public function to() : array
    {
        return $this->to;
    }

    public function from()
    {
        return $this->from;
    }

    public function cc() : array
    {
        return $this->cc;
    }

    public function bcc() : array
    {
        return $this->bcc;
    }

    public function subject() : string
    {
        return $this->subject;
    }

    public function body() : string
    {
        return $this->body;
    }

    public function replyTo()
    {
        return $this->replyTo;
    }

    public function sendAfter() : int
    {
        return $this->sendAfter;
    }

    public function addTo($email)
    {
        $this->to[] = $email;
        $this->to = array_unique($this->to);
    }

    public function setFrom($email)
    {
        $this->from = $email;
    }

    public function setSubject(string $subject)
    {
        $this->subject = $subject;
    }

    public function setBody(string $body)
    {
        $this->body = $body;
    }

    public function setReplyTo($email)
    {
        $this->replyTo = $email;
    }

    public function setSendAfter(int $date)
    {
        $this->sendAfter = $date;
    }

    public function addCC($email)
    {
        $this->cc[] = $email;
        $this->cc = array_unique($this->cc);
    }

    public function addBCC($email)
    {
        $this->bcc[] = $email;
        $this->bcc = array_unique($this->bcc);
    }
}
