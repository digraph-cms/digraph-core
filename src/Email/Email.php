<?php

namespace DigraphCMS\Email;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

class Email
{
    protected $uuid, $time, $sent, $category, $subject, $to, $to_uuid, $from, $cc, $bcc;
    protected $body_text, $body_html;
    protected $error = null;
    protected $exists = false;

    public static function newForEmail(
        string $category,
        string $email,
        string $subject,
        RichContent $body
    ): Email {
        return new Email(
            $category,
            $subject,
            $email,
            null,
            null,
            $body->html()
        );
    }

    /**
     * Return an array of identical emails, one for every address on file for
     * a given User.
     *
     * @param string $category
     * @param User $user
     * @param string $subject
     * @param RichContent $body
     * @return Email[]
     */
    public static function newForUser_all(
        string $category,
        User $user,
        string $subject,
        RichContent $body
    ): array {
        return array_map(
            function ($email) use ($category, $user, $subject, $body) {
                return new Email(
                    $category,
                    $subject,
                    $email,
                    $user->uuid(),
                    null,
                    $body->html()
                );
            },
            $user->emails()
        );
    }

    /**
     * Return an array of identical emails, one for every address on file for
     * a given User.
     *
     * @param string $category
     * @param User $user
     * @param string $subject
     * @param RichContent $body
     * @return Email|null
     */
    public static function newForUser(
        string $category,
        User $user,
        string $subject,
        RichContent $body
    ): ?Email {
        if (!$user->primaryEmail()) return null;
        return new Email(
            $category,
            $subject,
            $user->primaryEmail(),
            $user->uuid(),
            null,
            $body->html()
        );
    }

    public function __construct(
        string $category,
        string $subject,
        string $to,
        string $to_uuid = null,
        string $from = null,
        string $body_html,
        string $body_text = null,
        string $cc = null,
        string $bcc = null,
        string $uuid = null,
        int $time = null,
        int $sent = null,
        string $error = null,
        bool $exists = null
    ) {
        $this->category = $category;
        $this->subject = $subject;
        $this->to = $to;
        $this->to_uuid = $to_uuid;
        $this->from = $from ?? Config::get('email.from') ?? static::generateFrom();
        $this->body_html = $body_html;
        $this->body_text = $body_text ?? Emails::html2text($body_html);
        $this->cc = $cc ?? Config::get('email.cc');
        $this->bcc = $bcc ?? Config::get('email.bcc');
        $this->uuid = $uuid ?? Digraph::longUUID();
        $this->time = $time ?? time();
        $this->sent = $sent;
        $this->error = $error;
        $this->exists = $exists ?? false;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function isService(): bool
    {
        return !!Config::get('email.service_categories.' . $this->category());
    }

    protected static function generateFrom(): string
    {
        return 'noreply@' . parse_url(URLs::site(), PHP_URL_HOST);
    }

    public function setError(string $error)
    {
        $this->error = $error;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function url_unsubscribe(): URL
    {
        return new URL('/email_options/unsubscribe:' . $this->uuid());
    }

    public function url_manageSubscriptions(): URL
    {
        return new URL('/email_options/manage:' . $this->uuid());
    }

    public function url_adminInfo(): URL
    {
        return new URL('/admin/email/message:' . $this->uuid());
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function subject(): string
    {
        return strip_tags($this->subject);
    }

    public function from(): string
    {
        return $this->from;
    }

    public function to(): string
    {
        return $this->to;
    }

    public function cc(): ?string
    {
        return $this->cc;
    }

    public function bcc(): ?string
    {
        return $this->bcc;
    }

    public function toUUID(): ?string
    {
        return $this->to_uuid;
    }

    public function toUser(): ?User
    {
        return $this->to_uuid
            ? Users::get($this->to_uuid)
            : null;
    }

    public function body_html(): string
    {
        return $this->body_html;
    }

    public function body_text(): string
    {
        return $this->body_text;
    }

    public function sent(): ?DateTime
    {
        if ($this->sent) return (new DateTime)->setTimestamp($this->sent);
        else return null;
    }

    public function time(): DateTime
    {
        return (new DateTime)->setTimestamp($this->time);
    }

    public function timestamp(): int
    {
        return $this->time;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function categoryLabel(): string
    {
        return Emails::categoryLabel($this->category());
    }

    public function categoryDescription(): string
    {
        return Emails::categoryDescription($this->category());
    }
}
