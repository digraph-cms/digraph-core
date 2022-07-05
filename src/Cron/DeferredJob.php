<?php

namespace DigraphCMS\Cron;

use DateTime;
use DigraphCMS\Cache\Locking;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\Session\Session;

class DeferredJob
{
    protected $id, $group, $run, $error_time, $error_message, $job;

    public function __construct(callable $job = null, string $group = null)
    {
        $this->group = $this->group ?? $group ?? static::uuid();
        $this->job = $this->job !== null
            ? unserialize($this->job)
            : $job ?? function () {
                return 'Empty job';
            };
        // insert into database immediately
        if ($this->id() !== null) return;
        $this->id = DB::query()->insertInto(
            'defex',
            [
                '`group`' => $this->group(),
                'job' => $this->serializedJob()
            ]
        )->execute();
    }

    protected static function uuid(): string
    {
        return Digraph::uuid() . '_' . time();
    }

    public function execute(): bool
    {
        // only execute if ID exists, meaning this job is in the database
        if ($this->id() === null) return false;
        // try to get lock
        if (!($lock = Locking::lock('defex_' . $this->id(), false, 450))) return false;
        // override user
        Session::overrideUser('system');
        // execute
        try {
            $message = strval(call_user_func($this->job, $this));
            $error = false;
        } catch (\Throwable $th) {
            $message = get_class($th) . ': ' . $th->getMessage();
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
        Locking::release($lock);
        // return error state
        return !$error;
    }

    protected function serializedJob(): string
    {
        try {
            return serialize($this->job);
        } catch (\Throwable $th) {
            return \Opis\Closure\serialize($this->job);
        }
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

    public function errorTime(): ?DateTime
    {
        return $this->error_time
            ? (new DateTime)->setTimestamp($this->error_time)
            : null;
    }

    public function errorMessage(): ?string
    {
        return $this->error_message;
    }
}
