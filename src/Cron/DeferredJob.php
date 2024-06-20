<?php

namespace DigraphCMS\Cron;

use DateTime;
use DigraphCMS\Cache\Locking;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\ExceptionLog;
use DigraphCMS\Serializer;
use DigraphCMS\Session\Session;
use Exception;

class DeferredJob
{
    protected $id, $group, $run, $error, $message;
    /** @var callable|string|null */
    protected $job = null;
    /** @var int|null */
    protected $scheduled = null;

    public function __construct(callable $job = null, string $group = null, int|null $scheduled = null)
    {
        $this->group = $this->group ?? $group ?? static::uuid();
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
        $this->scheduled = $this->scheduled ?? $scheduled;
        // insert into database immediately
        if ($this->id() !== null) return;
        $this->id = DB::query()->insertInto(
            'defex',
            [
                '`group`' => $this->group(),
                'scheduled' => $this->scheduled(),
                'job' => $this->serializedJob()
            ]
        )->execute();
    }

    public function scheduled(): int|null
    {
        return $this->scheduled;
    }

    protected static function uuid(): string
    {
        return Digraph::uuid() . '_' . time();
    }

    public function execute(): bool
    {
        // only execute if ID exists, meaning this job is in the database
        if ($this->id() === null) return false;
        // also only execute if scheduledd time has arrived
        if ($this->scheduled() > time()) return false;
        // try to get lock
        $lock_id = 'defex/' . $this->id();
        if (!Locking::lock($lock_id, false, 300)) return false;
        // override user
        Session::overrideUser('system');
        // execute
        try {
            $message = strval(call_user_func($this->job, $this));
            $error = false;
        } catch (\Throwable $th) {
            ExceptionLog::log($th);
            if ($th instanceof Exception) {
                $message = get_class($th) . ': ' . $th->getMessage();
            } else {
                $message = get_class($th);
            }
            $error = true;
        }
        // write result to db
        DB::query()
            ->update(
                'defex',
                [
                    'run' => time(),
                    'message' => $message,
                    'error' => $error
                ],
                $this->id()
            )->execute();
        // remove override user
        Session::overrideUser(null);
        // release lock
        Locking::release($lock_id);
        // return error state
        return !$error;
    }

    protected function serializedJob(): string
    {
        return Serializer::serialize($this->job);
    }

    public function spawn(callable $job)
    {
        return new DeferredJob($job, $this->group());
    }

    public function spawnClone()
    {
        return new DeferredJob($this->job, $this->group());
    }

    public function group(): string
    {
        return $this->group;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function run(): ?DateTime
    {
        return $this->run
            ? (new DateTime)->setTimestamp($this->run)
            : null;
    }

    public function errorMessage(): ?string
    {
        return $this->message;
    }
}
