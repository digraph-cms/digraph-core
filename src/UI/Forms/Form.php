<?php

namespace DigraphCMS\UI\Forms;

use Formward\Form as FormwardForm;
use Formward\SystemFields\TokenNoCSRF;

class Form extends FormwardForm
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
            foreach ($this->callbacks as $callback) {
                $callback();
            }
        }
        return $result;
    }

    protected function setupToken()
    {
        if ($this->csrf() === false) {
            $this->systemFields['token'] = new TokenNoCSRF('Token field', 'token', $this);
        } else {
            $this->systemFields['token'] = new CsrfToken('Token field', 'token', $this);
        }
    }
}
