<?php

namespace DigraphCMS\Email;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Media\Media;
use DigraphCMS\UI\Templates;
use Html2Text\Html2Text;
use PHPMailer\PHPMailer\PHPMailer;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class Emails
{
    public static function unsubscribe(string $email, string $category)
    {
        if (static::isUnsubscribed($email, $category)) return;
        DB::query()->insertInto(
            'email_unsubscribe',
            [
                'email' => $email,
                'category' => $category,
                'time' => time()
            ]
        )->execute();
    }

    public static function resubscribe(string $email, string $category)
    {
        DB::query()->delete('email_unsubscribe')
            ->where('email = ? AND category = ?', [$email, $category])
            ->execute();
    }

    public static function isUnsubscribed(string $email, string $category)
    {
        return !!DB::query()->from('email_unsubscribe')
            ->where('email = ? AND category = ?', [$email, $category])
            ->count();
    }

    public static function categoryLabel(string $category): string
    {
        return Config::get('email.categories.' . $category . '.label')
            ?? ucwords(preg_replace('/[^a-z0-9]+/', ' ', $category)) . ' Emails';
    }

    public static function categoryDescription(string $category): string
    {
        return Config::get('email.categories.' . $category . '.description')
            ?? '<em>No email category description found</em>';
    }

    public static function send(Email $email)
    {
        // send email if it isn't blocked by unsubscribes
        if (!$email->blocked()) {
            if (Config::get('email.enabled')) {
                // sending emails is enabled
                try {
                    $mailer = static::mail();
                    $mailer->setFrom($email->from());
                    $mailer->addAddress($email->to());
                    if ($email->cc()) {
                        $mailer->addCC($email->cc());
                    }
                    if ($email->bcc()) {
                        $mailer->addBCC($email->bcc());
                    }
                    $mailer->Subject = $email->subject();
                    $mailer->msgHTML(static::prepareBody_html($email));
                    $mailer->AltBody = static::prepareBody_text($email);
                    $mailer->send();
                } catch (\Throwable $th) {
                    $email->setError($th->getMessage() . ' (' . get_class($th) . ')');
                }
            } else {
                // sending emails is disabled, record error in log
                $email->setError("Sending emails is disabled by config email.enabled");
            }
        }
        // insert record into database
        DB::query()->insertInto(
            'email_log',
            [
                'uuid' => $email->uuid(),
                'time' => time(),
                'category' => $email->category(),
                'subject' => $email->subject(),
                '`to`' => $email->to(),
                'to_uuid' => $email->toUUID(),
                '`from`' => $email->from(),
                'cc' => $email->cc(),
                'bcc' => $email->bcc(),
                'body_text' => $email->body_text(),
                'body_html' => $email->body_html(),
                'blocked' => $email->blocked(),
                'error' => $email->error()
            ]
        )->execute();
    }

    public static function get(?string $uuid): ?Email
    {
        if (!$uuid) return null;
        return static::select()
            ->where('uuid = ?', [$uuid])
            ->fetch();
    }

    public static function exists(?string $uuid): bool
    {
        if (!$uuid) return false;
        return !!DB::query()->from('email_log')
            ->where('uuid = ?', [$uuid])
            ->count();
    }

    public static function select(): EmailSelect
    {
        return new EmailSelect(
            DB::query()->from('email_log')
        );
    }

    public static function resultToEmail(array $row): Email
    {
        return new Email(
            $row['category'],
            $row['subject'],
            $row['to'],
            $row['to_uuid'],
            $row['from'],
            $row['body_html'],
            $row['body_text'],
            $row['cc'],
            $row['bcc'],
            $row['uuid'],
            $row['time'],
            $row['blocked']
        );
    }

    protected static function prepareBody_html(Email $email): string
    {
        if (Templates::exists('/email/html/body_' . $email->category() . '.php')) {
            $html = Templates::render('/email/html/body_' . $email->category() . '.php', ['email' => $email]);
        } else {
            $html = Templates::render('/email/html/body_default.php', ['email' => $email]);
        }
        $css = '';
        foreach (Media::glob('/styles_email/*.{scss,css}') as $file) {
            $css .= $file->content() . PHP_EOL;
        }
        return (new CssToInlineStyles)
            ->convert(
                $html,
                static::css()
            );
    }

    protected static function css(): string
    {
        static $css;
        if ($css === null) {
            $css = '';
            foreach (Media::glob('/styles_email/*.{scss,css}') as $file) {
                $css .= $file->content() . PHP_EOL;
            }
        }
        return $css;
    }

    protected static function prepareBody_text(Email $email): string
    {
        if (Templates::exists('/email/text/body_' . $email->category() . '.php')) {
            return Templates::render('/email/text/body_' . $email->category() . '.php', ['email' => $email]);
        } else {
            return Templates::render('/email/text/body_default.php', ['email' => $email]);
        }
    }

    public static function mail(): PHPMailer
    {
        $mail = new PHPMailer(true);
        return $mail;
    }

    /**
     * Convert HTML to text for use in the plaintext version that is bundled
     * with emails.
     *
     * @param string $html
     * @return string
     */
    public static function html2text(string $html): string
    {
        return (new Html2Text($html))
            ->getText();
    }

    /**
     * Determine whether a given email *should* be blocked according to current
     * un/subscription rules.
     *
     * @param Email $email
     * @return boolean
     */
    public static function shouldBlock(Email $email)
    {
        if ($email->category() == 'service') {
            return false;
        }
        return !!DB::query()->from('email_unsubscribe')
            ->where('email = ? AND category = ?', [$email->to(), $email->category()])
            ->count();
    }
}
