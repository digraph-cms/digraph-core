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
            return null;
        }
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'name' => $this->id()
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
     * @param string $value
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

    public function default(): ?string
    {
        return $this->default;
    }

    protected function submittedValue(): ?string
    {
        if ($this->form()->method() == FORM::METHOD_GET) {
            return Context::arg($this->id());
        } elseif ($this->form()->method() == FORM::METHOD_POST) {
            return Context::post($this->id());
        } else {
            return null;
        }
    }

    public function value($useDefault = false): ?string
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
            new Text(htmlspecialchars($this->value(true)))
        ];
    }
}
