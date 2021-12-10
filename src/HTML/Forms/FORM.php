<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;
use DigraphCMS\URL\URL;

class FORM extends Tag
{
    protected $tag = 'form';

    protected $method = 'post';
    protected $action;
    protected $token;
    protected $button;
    protected $callbacks = [];

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'form-' . self::$counter++);
        $this->addClass('form');
    }

    /**
     * Add a callback to be executed when a form is submitted and valid, happens
     * when the form is printed by default.
     * 
     * @param callable $callback
     * @return $this
     */
    public function addCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
        return $this;
    }

    public function ready(): bool
    {
        return $this->submitted() && $this->validate();
    }

    public function submitted(): bool
    {
        return $this->token()->submitted();
    }

    public function validate(): bool
    {
        $valid = true;
        foreach ($this->children() as $child) {
            if ($child instanceof InputInterface) {
                if ($child->validationError()) {
                    $valid = false;
                }
            }
        }
        return $valid;
    }

    public function children(): array
    {
        return array_merge(
            parent::children(),
            [
                $this->token(),
                $this->button()
            ]
        );
    }

    public function token(): Token
    {
        if (!$this->token) {
            $this->token = new Token($this);
            $this->token->setID('TOKEN');
        }
        return $this->token;
    }

    public function button(): SubmitButton
    {
        if (!$this->button) {
            $this->button = new SubmitButton();
        }
        return $this->button;
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes['method'] = $this->method();
        $attributes['action'] = $this->action();
        return $attributes;
    }

    public function method(): string
    {
        return $this->method;
    }

    /**
     * Set the method of this form. Should use class constants
     * FORM::METHOD_GET and FORM::METHOD_POST
     *
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
        return $this;
    }

    public function action(): URL
    {
        return $this->action ?? Context::url();
    }

    public function addChild($child)
    {
        if ($child instanceof InputInterface) {
            $child->setForm($this);
        }
        return parent::addChild($child);
    }

    public function toString(): string
    {
        // call callbacks when printed
        if ($this->ready()) {
            while ($this->callbacks) {
                call_user_func(array_shift($this->callbacks), $this);
            }
        }
        // return normal printing
        return parent::toString();
    }
}
