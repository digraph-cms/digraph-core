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
        $this->group = $this->group ?? $group ?? Digraph::uuid() . '_' . time();
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

    public function execute(): bool
    {
        if ($this->id() === null) return false;
        // try to get lock
        if (!Locking::lock('defex_' . $this->id())) return false;
        // override user
        Session::overrideUser('system');
        // only execute if ID exists, meaning this job is in the database
        DB::query()
            ->update('defex', ['run' => time()], $this->id())
            ->execute();
        try {
            DB::query()
                ->update(
                    'defex',
                    [
                        'message' => strval(call_user_func($this->job, $this)),
                        'error' => false
                    ],
                    $this->id()
                )->execute();
        } catch (\Throwable $th) {
            DB::query()
                ->update(
                    'defex',
                    [
                        'message' => get_class($th) . ': ' . $th->getMessage(),
                        'error' => true
                    ],
                    $this->id()
                )->execute();
        }
        // remove override user
        Session::overrideUser(null);
        // release lock
        Locking::release('defex_' . $this->id());
        // return true
        return true;
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
        return $this->spawn($this->job);
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
