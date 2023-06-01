<?php

namespace DigraphCMS\Email;

use DateInterval;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Media\Media;
use DigraphCMS\UI\Templates;
use Exception;
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

    /**
     * @psalm-suppress PossiblyInvalidArgument
     * @psalm-suppress InvalidArgument
     * @return array
     */
    public static function existingCategories(): array
    {
        return array_unique(array_merge(
            array_keys(Config::get('email.categories')),
            array_map(
                function (array $row): string {
                    return $row['category'];
                },
                DB::query()->from('email')
                    ->select('DISTINCT category', true)
                    ->fetchAll(0)
            )
        ));
    }

    /**
     * Enqueue an Email or array of Emails to be sent later.
     *
     * @param Email|Email[] $email
     * @return void
     */
    public static function queue($email)
    {
        // recurse into arrays
        if (is_array($email)) {
            foreach ($email as $msg) static::queue($msg);
            return;
        }
        // do nothing if email is blocked
        if (Emails::shouldBlock($email)) return;
        // save into database
        DB::query()->insertInto(
            'email',
            [
                'uuid' => $email->uuid(),
                'time' => time(),
                'sent' => null,
                'category' => $email->category(),
                'subject' => $email->subject(),
                '`to`' => $email->to(),
                'to_uuid' => $email->toUUID(),
                '`from`' => $email->from(),
                'cc' => $email->cc(),
                'bcc' => $email->bcc(),
                'body_text' => $email->body_text(),
                'body_html' => $email->body_html(),
                'error' => $email->error()
            ]
        )->execute();
    }

    public static function quotaReached(): bool
    {
        if (!Config::get('email.quota')) return false;
        if (Config::get('email.quota.mode') == 'rolling') {
            $interval = new DateInterval(Config::get('email.quota.interval'));
            $start = (new DateTime)->sub($interval)->getTimestamp();
            $count = static::select()
                ->where('sent >= ?', [$start])
                ->count();
            return $count >= Config::get('email.quota.count');
        }
        return false;
    }

    /**
     * Send an Email or array of Emails now if possible, or queue for later if
     * quota is reached. Optionally ignore the quota check.
     *
     * @param Email|Email[] $email
     * @param boolean $ignoreQuota
     * @return void
     */
    public static function send($email, bool $ignoreQuota = false)
    {
        // recurse into arrays
        if (is_array($email)) {
            foreach ($email as $msg) static::send($msg, $ignoreQuota);
            return;
        }
        // do nothing if email is blocked
        if (Emails::shouldBlock($email)) return;
        // send email if enabled, otherwise it gets an error so that we can test
        // what emails would have been sent
        if (Config::get('email.enabled') || in_array($email->to(), Config::get('email.enabled_for'))) {
            // check if we should enqueue instead
            if (!$ignoreQuota && static::quotaReached()) {
                if (!$email->exists()) static::queue($email);
                return;
            }
            // sending emails is enabled
            try {
                $mailer = static::mailer();
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
                if ($th instanceof Exception) {
                    $email->setError($th->getMessage() . ' (' . get_class($th) . ')');
                } else {
                    $email->setError(get_class($th));
                }
            }
        } else {
            // sending emails is disabled, record error in log
            $email->setError("Sending emails is disabled by config email.enabled");
        }
        // insert record into database, or update the sent/error results
        if ($email->exists()) {
            // update sent time and error message if message exists
            DB::query()->update(
                'email',
                [
                    'sent' => time(),
                    'error' => $email->error(),
                ]
            )
                ->where('uuid', $email->uuid())
                ->execute();
        } else {
            // otherwise insert message
            DB::query()->insertInto(
                'email',
                [
                    'uuid' => $email->uuid(),
                    'time' => time(),
                    'sent' => time(),
                    'category' => $email->category(),
                    'subject' => $email->subject(),
                    '`to`' => $email->to(),
                    'to_uuid' => $email->toUUID(),
                    '`from`' => $email->from(),
                    'cc' => $email->cc(),
                    'bcc' => $email->bcc(),
                    'body_text' => $email->body_text(),
                    'body_html' => $email->body_html(),
                    'error' => $email->error()
                ]
            )->execute();
        }
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
        return !!DB::query()->from('email')
            ->where('uuid = ?', [$uuid])
            ->count();
    }

    public static function select(): EmailSelect
    {
        return new EmailSelect(
            DB::query()->from('email')
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
            $row['sent'],
            $row['error'],
            true
        );
    }

    public static function prepareBody_html(Email $email): string
    {

        Context::beginEmail();
        if (Templates::exists('/email/html/body_' . $email->category() . '.php')) {
            $html = Templates::render('/email/html/body_' . $email->category() . '.php', ['email' => $email]);
        } else {
            $html = Templates::render('/email/html/body_default.php', ['email' => $email]);
        }
        $output = (new CssToInlineStyles)
            ->convert(
                $html,
                static::css()
            );
        Context::end();
        return $output;
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

    public static function prepareBody_text(Email $email): string
    {
        if (Templates::exists('/email/text/body_' . $email->category() . '.php')) {
            return Templates::render('/email/text/body_' . $email->category() . '.php', ['email' => $email]);
        } else {
            return Templates::render('/email/text/body_default.php', ['email' => $email]);
        }
    }

    public static function beginBatch()
    {
        if (!static::useSMTP()) return;
        static::mailer()->SMTPKeepAlive = true;
    }

    public static function endBatch()
    {
        if (!static::useSMTP()) return;
        static::mailer()->smtpClose();
        static::mailer()->SMTPKeepAlive = false;
    }

    protected static function useSMTP(): bool
    {
        return Config::get('email.use_smtp') && Config::get('email.smtp');
    }

    protected static function mailer(): PHPMailer
    {
        static $mailer;
        // set up and configure mailer
        if (!$mailer) {
            $mailer = new PHPMailer(true);
            // set up smtp if configured
            if (static::useSMTP()) {
                $smtp = Config::get('email.smtp');
                $mailer->isSMTP();
                $mailer->Host = $smtp['host'];
                if ($smtp['auth']) {
                    $mailer->SMTPAuth = true;
                    $mailer->Username = $smtp['user'];
                    $mailer->Password = $smtp['pass'];
                    if ($smtp['security'] == 'TLS') {
                        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mailer->Port = $smtp['port'] ?? 465;
                    } elseif ($smtp['security'] == 'STARTTLS') {
                        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mailer->Port = $smtp['port'] ?? 587;
                    } else {
                        $mailer->Port = $smtp['port'] ?? 25;
                    }
                }
                if ($smtp['options']) {
                    $mailer->SMTPOptions = $smtp['options'];
                }
                $mailer->SMTPAutoTLS = $smtp['autotls'];
            }
            // otherwise explicitly set issendmail
            else {
                $mailer->isSendmail();
            }
        }
        // reset mailer for sending a new email
        $mailer->Body = '';
        $mailer->AltBody = '';
        $mailer->Subject = '';
        $mailer->CharSet = 'UTF-8';
        $mailer->clearAllRecipients();
        $mailer->clearAttachments();
        $mailer->clearCustomHeaders();
        $mailer->clearReplyTos();
        // return mailer
        return $mailer;
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
        if ($email->isService()) {
            return false;
        }
        return !!DB::query()->from('email_unsubscribe')
            ->where('email = ? AND category = ?', [$email->to(), $email->category()])
            ->count();
    }
}
