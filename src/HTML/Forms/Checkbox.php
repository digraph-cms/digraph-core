<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;

class Checkbox extends Tag implements InputInterface
{
    protected $tag = 'input';
    protected $void = true;

    /** @var FormWrapper */
    protected $form;
    protected $default;
    protected $value;
    protected $required = false;
    protected $requiredMessage = 'This field is required';
    protected $validators = [];

    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'checkbox-' . self::$counter++);
    }

    public function validationError(): ?string
    {
        if ($this->required() && !$this->value()) {
            return $this->requiredMessage;
        } else {
            foreach ($this->validators as $validator) {
                if ($message = call_user_func($validator, $this)) {
                    return $message;
                }
            }
            return null;
        }
    }

    /**
     * Set a validator function for this input. Callable should return a string with an
     * error message if invalid, or otherwise null.
     *
     * @param callable $validator
     * @return static
     */
    public function addValidator(callable $validator)
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function attributes(): array
    {
        $attributes = array_merge(
            parent::attributes(),
            [
                'name' => $this->id(),
                'type' => 'checkbox',
                'form' => $this->form() ? $this->form()->formID() : null
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
     * @param mixed $value
     * @return static
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
     * @param mixed $value
     * @return static
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function form(): ?FormWrapper
    {
        return $this->form;
    }

    public function submitted(): bool
    {
        if ($this->form()) {
            return $this->form()->submitted();
        } else {
            return false;
        }
    }

    public function setForm(FormWrapper $form)
    {
        $this->form = $form;
        return $this;
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
        if ($this->submitted() && $this->form()->method() == FormWrapper::METHOD_GET) {
            return Context::arg($this->id()) == 'on';
        } elseif ($this->submitted() && $this->form()->method() == FormWrapper::METHOD_POST) {
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
