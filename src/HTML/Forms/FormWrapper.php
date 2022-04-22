<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;
use DigraphCMS\URL\URL;

class FormWrapper extends Tag
{
    protected $tag = 'div';

    protected $method = 'post';
    protected $action;
    protected $token;
    protected $button;
    protected $form;
    protected $callbacks = [];

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'form-' . self::$counter++);
        $this->addClass('form-wrapper');
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
                $this->button(),
                $this->form()
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

    public function form(): FORM
    {
        if (!$this->form) {
            $this->form = (new FORM())
                ->addClass('detached-form');
        }
        return $this->form;
    }

    public function button(): SubmitButton
    {
        if (!$this->button) {
            $this->button = (new SubmitButton())
                ->setForm($this);
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
     * FormWrapper::METHOD_GET and FormWrapper::METHOD_POST
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
        if (method_exists($child, 'setForm')) {
            $child->setForm($this);
        }
        return parent::addChild($child);
    }

    public function formID(): string
    {
        return md5($this->id());
    }

    public function toString(): string
    {
        // recursively set form on children
        $this->setForm($this->children());
        // call callbacks when printed
        if ($this->ready()) {
            while ($this->callbacks) {
                call_user_func(array_shift($this->callbacks), $this);
            }
        }
        // set up actual form tag
        foreach ($this->attributes() as $k => $v) $this->form()->setAttribute($k, $v);
        $this->form()
            ->setAttribute('method', $this->method())
            ->setAttribute('action', $this->action())
            ->setID($this->formID());
        // return normal printing
        return parent::toString();
    }

    protected function setForm(array $children)
    {
        foreach ($children as $child) {
            if ($child instanceof Tag) {
                if (method_exists($child, 'setForm')) {
                    $child->setForm($this);
                }
                $this->setForm($child->children());
            }
        }
    }
}
