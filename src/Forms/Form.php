<?php

namespace DigraphCMS\Forms;

use DigraphCMS\DB\DB;

class Form extends \Formward\Form
{
    protected $callbacks = [];

    public function addCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    public function handle(?callable $validFn = null, ?callable $invalidFn = null, ?callable $notSubmittedFn = null): ?bool
    {
        $result = parent::handle($validFn, $invalidFn, $notSubmittedFn);
        if ($result) {
            DB::beginTransaction();
            foreach ($this->callbacks as $callback) {
                $callback();
            }
            DB::commit();
        }
        return $result;
    }
}
