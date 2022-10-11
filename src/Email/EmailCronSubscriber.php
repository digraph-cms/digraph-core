<?php

namespace DigraphCMS\Email;

use DigraphCMS\Config;
use DigraphCMS\Cron\CronJob;

class EmailCronSubscriber
{
    /**
     * Send queued emails for as long as specified in either a deadline time
     * passed in because we're running on poor man's cron, or for the duration
     * specified by email.cron_time
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
        $queue = Emails::select()->queue();
        Emails::beginBatch();
        while (time() < $deadlineTime && $email = $queue->fetch()) {
            Emails::send($email);
        }
        Emails::endBatch();
    }
}
