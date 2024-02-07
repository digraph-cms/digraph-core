<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;
use DigraphCMS\Security\SecureContent;
use DigraphCMS\Security\Security;
use DigraphCMS\URL\URL;

class FormWrapper extends Tag
{
    protected $tag = 'div';
    protected string $method = 'post';
    protected URL|null $action = null;
    protected Token|null $token = null;
    protected SubmitButton|null $button = null;
    protected FORM|null $form = null;
    protected bool $captcha = true;
    protected bool $displayChildren = true;
    /** @var callable[] */
    protected array $callbacks = [];
    /** @var callable[] */
    protected array $preValidationCallbacks = [];
    protected bool $display_validation_error = false;
    protected bool $enable_validation_error = true;

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    /** @var int */
    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'form-' . self::$counter++);
        $this->addClass('form-wrapper');
    }

    /**
     * Set whether CAPTCHA verification is required for this form. Note that
     * this will not necessarily show a CAPTCHA if the user is signed in or has
     * already completed a CAPTCHA in this session.
     * @param bool $captcha 
     * @return static 
     */
    public function setCaptcha(bool $captcha): static
    {
        $this->captcha = $captcha;
        return $this;
    }

    /**
     * Whether or not CAPTCHA verifiation is required for this form.
     * @return bool 
     */
    public function captcha(): bool
    {
        return $this->captcha;
    }

    /**
     * Add a callback to be executed when a form is submitted and valid, happens
     * when the form is printed by default.
     * 
     * @param callable $callback
     * @return static
     */
    public function addCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
        return $this;
    }

    /**
     * Add a callback to be executed when a form is submitted, before validation
     * runs. Useful for controlling when one field may or may not require 
     * additional validation depending on the value of another field.
     * 
     * @param callable $callback 
     * @return static 
     */
    public function addPreValidationCallback(callable $callback): static
    {
        $this->preValidationCallbacks[] = $callback;
        return $this;
    }

    public function ready(): bool
    {
        return $this->submitted() && $this->validate();
    }

    public function submitted(): bool
    {
        if ($this->captcha() && Security::flagged()) {
            return false;
        }
        return $this->token()->submitted();
    }

    public function validate(): bool
    {
        // run pre-validation callbacks
        while ($this->preValidationCallbacks) {
            call_user_func(array_shift($this->preValidationCallbacks), $this);
        }
        // run validation
        $valid = true;
        foreach ($this->children() as $child) {
            if (is_object($child) && method_exists($child, 'validationError')) {
                if ($child->validationError()) {
                    $valid = false;
                }
            }
        }
        return $valid;
    }

    /**
     * Set whether or not to display a validation error message at the top of the form
     *
     * @param boolean $enable
     * @return void
     */
    public function setEnableValidationError(bool $enable)
    {
        $this->enable_validation_error = $enable;
        return $this;
    }

    public function displayChildren(): bool
    {
        return $this->displayChildren;
    }

    /**
     * Set whether or not children should be displayed here. Used to allow rendering
     * inputs separately, such as in a table.
     *
     * @param boolean $set
     * @return static
     */
    public function setDisplayChildren(bool $set)
    {
        $this->displayChildren = $set;
        return $this;
    }

    public function children(): array
    {
        $children = $this->displayChildren() ? parent::children() : [];
        if ($this->display_validation_error) {
            array_unshift($children, '<div class="notification notification--error notification--form-validation">Please correct any form validation errors below</div>');
        }
        if ($this->captcha()) {
            $children[] = (new SecureContent($this->id() . '__captcha'))
                ->addChild($this->token())
                ->addChild($this->button())
                ->addChild($this->form());
        } else {
            $children[] = $this->token();
            $children[] = $this->button();
            $children[] = $this->form();
        }
        return $children;
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
     * @return static
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set the URL this form should direct to
     *
     * @param URL|null $action
     * @return static
     */
    public function setAction(?URL $action)
    {
        $this->action = $action;
        return $this;
    }

    public function action(): URL
    {
        return $this->action ?? Context::url();
    }

    public function addChild($child)
    {
        if (is_a($child, InputInterface::class) || method_exists($child, 'setForm')) {
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
        $this->setChildrenForms($this->children());
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
        // check if we want to display a validation error
        if ($this->displayChildren() && $this->submitted() && !$this->validate()) {
            $this->display_validation_error = true;
        } else {
            $this->display_validation_error = false;
        }
        // return normal printing
        return parent::toString();
    }

    /**
     * @param array $children
     * @return void
     */
    protected function setChildrenForms(array $children)
    {
        foreach ($children as $child) {
            if ($child instanceof Tag) {
                if (is_a($child, InputInterface::class) || method_exists($child, 'setForm')) {
                    $child->setForm($this);
                }
                $this->setChildrenForms($child->children());
            }
        }
    }
}
