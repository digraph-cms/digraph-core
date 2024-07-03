<?php

namespace DigraphCMS\Cron;

use DateTime;
use DigraphCMS\Cache\Locking;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\ExceptionLog;
use DigraphCMS\Serializer;
use DigraphCMS\Session\Session;
use Exception;

class CronJob
{
    protected $id, $parent, $name, $interval;
    protected $run_next, $run_last;
    protected $error_time, $error_message;
    /** @var callable|string|null */
    protected $job = null;

    public function __construct(string $parent = null, string $name = null, callable $job = null, string $interval = null)
    {
        $this->parent = $this->parent ?? $parent;
        $this->name = $this->name ?? $name;
        if ($this->job === null) {
            // $this->job is empty, so we're creating a new job
            $this->job = $job ?? function () {
                return 'Empty job';
            };
        } elseif (is_string($this->job)) {
            // $this->job is not empty, so we're loading an existing job from the database
            $this->job = Serializer::unserialize($this->job);
        } else {
            throw new Exception('Invalid job data');
        }
        $this->run_next = $this->run_next ?? time();
        $this->interval = $this->interval ?? $interval ?? 'default';
        // insert into database immediately if not already in there
        // "already in there" keys off parent/name pair
        if ($this->id() === null) {
            $this->id = DB::query()->insertInto(
                'cron',
                [
                    'parent' => $this->parent(),
                    '`name`' => $this->name(),
                    '`interval`' => $this->interval(),
                    'run_next' => $this->runNext(),
                    'run_last' => $this->runLast(),
                    'error_time' => $this->errorTime(),
                    'error_message' => $this->errorMessage(),
                    'job' => $this->serializedJob()
                ]
            )->execute();
        }
    }

    public function execute(int $deadlineTime = null)
    {
        if ($this->id() === null) return false;
        // try to get lock
        $lock_id = 'cron/' . $this->id();
        if (!Locking::lock($lock_id, false, 450)) return false;
        // override user
        Session::overrideUser('system');
        // only execute if ID exists, meaning this job is in the database
        $this->run_last = time();
        $this->computeNextRun();
        $row = [
            'run_last' => $this->run_last,
            'run_next' => $this->run_next
        ];
        try {
            $error = false;
            if ($this->job()) {
                call_user_func($this->job(), $this, $deadlineTime);
            }
        } catch (\Throwable $th) {
            ExceptionLog::log($th);
            $error = true;
            $row['error_time'] = time();
            if ($th instanceof Exception) {
                $row['error_message'] = get_class($th) . ': ' . $th->getMessage();
            } else {
                $row['error_message'] = get_class($th);
            }
        }
        // save results to db
        DB::query()
            ->update('cron', $row, $this->id())
            ->execute();
        // remove override on user
        Session::overrideUser(null);
        // release lock
        Locking::release($lock_id);
        // return error state
        return !$error;
    }

    protected function computeNextRun()
    {
        $this->run_next = new DateTime(Config::get('cron.intervals.' . $this->interval) ?? Config::get('cron.intervals.default'));
        $this->run_next = $this->run_next->getTimestamp();
    }

    public function id(): ?int
    {
        // try to figure out our ID if we don't know it
        if ($this->id === null) {
            $q = DB::query()->from('cron')
                ->where('parent = ? AND name = ?', [$this->parent, $this->name]);
            if ($r = $q->fetch()) {
                $this->id = $r['id'];
            } else {
                $this->id = false;
            }
        }
        // return our ID
        return $this->id === false ? null : $this->id;
    }

    public function delete()
    {
        if ($this->id() === null) return;
        DB::query()
            ->delete('cron', $this->id())
            ->execute();
    }

    public function parent(): string
    {
        return $this->parent;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function interval(): string
    {
        return $this->interval;
    }

    public function runNext(): ?int
    {
        return $this->run_next;
    }

    public function runLast(): ?int
    {
        return $this->run_last;
    }

    public function errorTime(): ?int
    {
        return $this->error_time;
    }

    public function errorMessage(): ?string
    {
        return $this->error_message;
    }

    public function job(): ?callable
    {
        if (!is_callable($this->job)) {
            $this->recordError('Job is not callable, it may reference a plugin or feature that no longer exists');
            return null;
        }
        return $this->job;
    }

    public function serializedJob(): string
    {
        try {
            return serialize($this->job);
        } catch (\Throwable $th) {
            return Serializer::serialize($this->job);
        }
    }

    public function recordError(string $message)
    {
        if ($this->id() === null) return;
        DB::query()->update(
            'cron',
            [
                'error_time' => time(),
                'error_message' => $message
            ],
            $this->id()
        )->execute();
    }
}
