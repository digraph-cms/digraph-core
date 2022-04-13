<?php

namespace DigraphCMS\Cron;

use DateTime;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;

class CronJob
{
    protected $id, $parent, $name, $interval;
    protected $run_next, $run_last, $run_halted;
    protected $error_time, $error_message;
    protected $job;

    public function __construct(string $parent = null, string $name = null, callable $fn = null, string $interval = null)
    {
        $this->parent = $parent ?? $this->parent;
        $this->name = $name ?? $this->name;
        $this->job = $fn ?? unserialize($this->job);
        $this->run_next = $this->run_next ?? time();
        $this->interval = $interval ?? $this->interval ?? 'default';
    }

    public function run()
    {
        error_log('running job ' . $this->id());
        $this->run_last = time();
        $this->computeNextRun();
        DB::query()->update(
            'cron',
            [
                'run_last' => $this->run_last,
                'run_next' => $this->run_next
            ],
            $this->id()
        )->execute();
        if ($this->job()) {
            call_user_func($this->job(), $this);
        }
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

    public function runHalted(): ?int
    {
        return $this->run_halted;
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
            $this->halt('Job is not callable, it may reference a plugin or feature that no longer exists');
            return null;
        }
        return $this->job;
    }

    public function serializedJob(): string
    {
        try {
            return serialize($this->job);
        } catch (\Throwable $th) {
            return \Opis\Closure\serialize($this->job);
        }
    }

    public function recordError(string $message)
    {
        if (!$this->id()) return;
        DB::query()->update(
            'cron',
            [
                'error_time' => time(),
                'error_message' => $message
            ],
            $this->id()
        )->execute();
    }

    public function halt(string $message)
    {
        if (!$this->id()) return;
        DB::query()->update(
            'cron',
            [
                'run_halted' => time(),
                'error_time' => time(),
                'error_message' => 'HALTED: ' . $message
            ],
            $this->id()
        )->execute();
    }

    public function restart()
    {
        if (!$this->id()) return;
        DB::query()->update(
            'cron',
            [
                'run_halted' => null,
                'run_next' => time()
            ],
            $this->id()
        )->execute();
    }

    public function save(bool $forceUpdateJob = null, bool $forceUpdateNextRun = null)
    {
        $row = [
            'parent' => $this->parent(),
            'name' => $this->name(),
            'interval' => $this->interval(),
            'run_next' => $this->runNext(),
            'run_last' => $this->runLast(),
            'run_halted' => $this->runHalted(),
            'error_time' => $this->errorTime(),
            'error_message' => $this->errorMessage(),
            'job' => $this->serializedJob()
        ];
        if ($this->id()) {
            unset($row['parent']);
            unset($row['name']);
            if (!$forceUpdateNextRun) unset($row['run_next']);
            if (!$forceUpdateJob) unset($row['job']);
            DB::query()->update(
                'cron',
                $row,
                $this->id()
            )->execute();
        } else {
            $this->id = DB::query()->insertInto(
                'cron',
                $row
            )->execute();
        }
    }
}
