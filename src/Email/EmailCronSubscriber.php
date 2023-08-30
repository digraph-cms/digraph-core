<?php

namespace DigraphCMS\Email;

use DateInterval;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Cron\CronJob;
use DigraphCMS\DB\DB;

class EmailCronSubscriber
{

    /**
     * Send queued emails for as long as specified in either a deadline time
     * passed in, or for the duration specified by email.cron_time
     * 
     * When using SMTP queued emails are significantly more efficient to send,
     * because many can be sent at a time through cron reusing a single 
     * connection, instead of having SMTP connection overhead for every message.
     * 
     * @param CronJob $job
     * @param integer|null $deadlineTime
     * @return void
     */
    public static function cronJob_email(CronJob $job, int $deadlineTime = null)
    {
        $deadlineTime = $deadlineTime ?? (Config::get('email.cron_time') + time());
        $queue = Emails::select()
            ->queue()
            ->limit(Config::get('email.cron_count'));
        Emails::beginBatch();
        while (time() < $deadlineTime && $email = $queue->fetch()) {
            Emails::send($email);
        }
        Emails::endBatch();
    }

    /**
     * Heavy maintenance job to expire old emails from the database after an
     * amount of time defined in config email.expiration_interval
     *
     * @param CronJob $job
     * @param integer $deadlineTime
     * @return void
     */
    public static function cronJob_maintenance_heavy(CronJob $job, int $deadlineTime): void
    {
        DB::query()
            ->delete('email')
            ->where(
                'sent < ?',
                (new DateTime)
                    ->sub(new DateInterval(Config::get('email.expiration_interval')))
                    ->getTimestamp()
            )->execute();
    }

}