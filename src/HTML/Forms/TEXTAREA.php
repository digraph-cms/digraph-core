<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;
use DigraphCMS\HTML\Text;

class TEXTAREA extends Tag implements InputInterface
{
    protected $tag = 'textarea';
    protected $void = false;

    protected $form;
    protected $default;
    protected $value;
    protected $required = false;
    protected $requiredMessage = 'This field is required';
    protected $validators = [];

    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'textarea-' . self::$counter++);
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
        return array_merge(
            parent::attributes(),
            [
                'name' => $this->id(),
                'form' => $this->form() ? $this->form()->formID() : null
            ]
        );
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
     * @param string $value
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
     * @param string $value
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

    protected function submittedValue(): ?string
    {
        if ($this->form() && $this->form()->method() == FormWrapper::METHOD_GET) {
            return Context::arg($this->id());
        } elseif ($this->form() && $this->form()->method() == FormWrapper::METHOD_POST) {
            return Context::post($this->id());
        } else {
            return null;
        }
    }

    public function value($useDefault = false)
    {
        if ($this->value) {
            return $this->value;
        } elseif (($value = $this->submittedValue()) || $this->submitted()) {
            return $value ? $value : null;
        } elseif ($useDefault) {
            return $this->default();
        } else {
            return null;
        }
    }

    public function addChild($child)
    {
        throw new \Exception("Can't add children to a TEXTAREA");
    }

    public function children(): array
    {
        return [
            new Text(htmlentities($this->value(true) ?? ''))
        ];
    }
}
