<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;

class Checkbox extends Tag implements InputInterface
{
    protected $tag = 'input';
    protected $void = true;

    protected $form;
    protected $default;
    protected $value;
    protected $required = false;
    protected $requiredMessage = 'This field is required';

    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'input-' . self::$counter++);
    }

    public function validationError(): ?string
    {
        if ($this->required() && !$this->value()) {
            return $this->requiredMessage;
        } else {
            return null;
        }
    }

    public function attributes(): array
    {
        $attributes = array_merge(
            parent::attributes(),
            [
                'name' => $this->id(),
                'type' => 'checkbox'
            ]
        );
        if ($this->value(true)) {
            $attributes['checked'] = null;
        }
        return $attributes;
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required, string $message = null)
    {
        $this->required = $required;
        $this->requiredMessage = $message ?? $this->requiredMessage;
        return $this;
    }

    /**
     * Set the default value of this input, to be used if no value is
     * submitted in the get/post values.
     *
     * @param $value
     * @return $this
     */
    public function setDefault($value)
    {
        $this->default = $value;
        return $this;
    }

    /**
     * Set the value of this input explicitly. It will not respond to
     * different submitted values from this point onward.
     *
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function form(): ?FORM
    {
        return $this->form;
    }

    public function submitted(): bool
    {
        if ($this->form()) {
            return $this->form()->submitted();
        } else {
            return !!$this->submittedValue();
        }
    }

    public function setForm(FORM $form)
    {
        $this->form = $form;
    }

    public function id(): ?string
    {
        if ($this->form()) {
            return $this->form()->id() . '--' . parent::id();
        } else {
            return parent::id();
        }
    }

    public function default(): ?bool
    {
        return $this->default;
    }

    protected function submittedValue(): ?bool
    {
        if ($this->submitted() && $this->form()->method() == FORM::METHOD_GET) {
            return Context::arg($this->id()) == 'on';
        } elseif ($this->submitted() && $this->form()->method() == FORM::METHOD_POST) {
            return Context::post($this->id()) == 'on';
        } else {
            return null;
        }
    }

    public function value($useDefault = false): ?bool
    {
        if ($this->value !== null) {
            return $this->value;
        } elseif ($this->submittedValue() !== null || $this->submitted()) {
            return $this->submittedValue();
        } elseif ($useDefault) {
            return $this->default();
        } else {
            return null;
        }
    }
}