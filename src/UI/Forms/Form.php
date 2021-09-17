<?php

namespace DigraphCMS\UI\Forms;

use DigraphCMS\Context;
use DigraphCMS\UI\Theme;
use Formward\AbstractContainer;
use Formward\FieldInterface;
use Formward\SystemFields\TokenNoCSRF;

class Form extends \Formward\Form
{
    protected $callbacks = [];

    public function __construct(string $label, string $name = null, FieldInterface $parent = null)
    {
        parent::__construct($label, $name, $parent);
        static::load();
        $this->action(Context::url());
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

    public function wrapperContentOrder(): array
    {
        return [
            '{tips}',
            '{field}'
        ];
    }

    /**
     * Add system fields to html tag content
     */
    protected function htmlContent(): ?string
    {
        //basic output
        $out = [
            '<label>' . $this->label() . '</label>',
            $this->validationMessagesHTML(),
            preg_replace('/<label>.*?<\/label>/', '', AbstractContainer::htmlContent())
        ];
        //add system fields if necessary
        if ($this->systemFields) {
            $out[] = implode(
                PHP_EOL,
                array_map(
                    function ($i) {
                        return $this->containerItemHtml($i);
                    },
                    $this->systemFields
                )
            );
        }
        return implode(PHP_EOL, $out);
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
