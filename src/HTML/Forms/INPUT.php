<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;

class INPUT extends Tag
{
    protected $tag = 'input';
    protected $block = false;
    protected $void = true;

    protected $form;
    protected $default;
    protected $value;
    protected $required = false;

    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'input-' . self::$counter++);
    }

    public function attributes(): array
    {
        return array_merge_recursive(
            parent::attributes(),
            [
                'value' => $this->value()
            ]
        );
    }

    public function required(): bool
    {
        return $this->required;
    }

    /**
     * Set the default value of this input, to be used if no value is
     * submitted in the get/post values.
     *
     * @param string $value
     * @return $this
     */
    public function setDefault(string $value)
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
    public function setValue(string $value)
    {
        $this->value = $value;
    }

    public function form(): ?FORM
    {
        return $this->form;
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

    public function value(): ?string
    {
        if ($this->value !== null) {
            return $this->value;
        } elseif ($this->form()) {
            if ($this->form()->method() == FORM::METHOD_GET) {
                return Context::arg($this->id()) ?? $this->default();
            } elseif ($this->form()->method() == FORM::METHOD_POST) {
                return Context::post($this->id()) ?? $this->default();
            }
        } else {
            return $this->default();
        }
    }
}
