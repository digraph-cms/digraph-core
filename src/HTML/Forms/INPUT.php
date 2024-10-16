<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;

class INPUT extends Tag implements InputInterface
{
    protected $tag = 'input';
    protected $void = true;

    protected $form;
    protected $default;
    protected $value;
    protected $required = false;
    protected $requiredMessage = 'This field is required';
    protected $validators = [];
    protected bool $disabled = false;

    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'input-' . crc32(get_called_class() . static::$counter++));
    }

    public function disabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;
        return $this;
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
                'value' => $this->value(true),
                'name' => $this->id(),
                'form' => $this->form() ? $this->form()->formID() : null
            ]
        );
        if ($this->disabled()) {
            $attributes['disabled'] = null;
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
     * @param string|null $value
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
     * @param string|null $value
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
            return !!$this->submittedValue();
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

    public function default()
    {
        return $this->default;
    }

    protected function submittedValue()
    {
        if ($this->form() && $this->form()->method() == FormWrapper::METHOD_GET) {
            return Context::arg($this->id());
        } elseif ($this->form() && $this->form()->method() == FormWrapper::METHOD_POST) {
            return Context::post($this->id());
        } else {
            return null;
        }
    }

    public function value(bool $useDefault = false): mixed
    {
        if ($this->value) {
            return $this->value;
        } elseif (($value = trim($this->submittedValue() ?? "")) || $this->submitted()) {
            return $value ? $value : null;
        } elseif ($useDefault) {
            return $this->default();
        } else {
            return null;
        }
    }
}
