<?php

namespace DigraphCMS\UI\Forms;

use DigraphCMS\UI\Theme;
use Formward\FieldInterface;
use Formward\SystemFields\TokenNoCSRF;

class Form extends \Formward\Form
{
    protected $callbacks = [];

    public function __construct(string $label, string $name = null, FieldInterface $parent = null)
    {
        parent::__construct($label, $name, $parent);
        static::load();
    }

    public static function load()
    {
        static $loaded = false;
        if (!$loaded) {
            $loaded = true;
            Theme::addBlockingPageCss('/core/formward.css');
        }
    }

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
