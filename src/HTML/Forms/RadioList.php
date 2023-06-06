<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Fields\RadioField;

class RadioList extends DIV implements InputInterface
{
    protected static $counter = 0;
    protected $fields = [];
    protected $form;
    protected $required = false;
    protected $requiredMessage = 'You must select an option';
    protected $validators = [];

    public function __construct(array $options = [])
    {
        $this->setID('radio-list-' . static::$counter++);
        $this->addClass('radio-list');
        foreach ($options as $key => $label) {
            $this->addOption($key, $label);
        }
    }

    public function children(): array
    {
        return array_merge(
            parent::children(),
            array_map(
                function ($f) {
                    $f['field']->setForm($this->form());
                    return $f['field'];
                },
                $this->fields
            )
        );
    }

    public function addOption(string $key, string $label, string $value = null)
    {
        $value = $value ?? $key;
        $field = (new RadioField($label, $this->id() . '--option', $key));
        $this->fields[$key] = [
            'field' => $field,
            'value' => $value
        ];
    }

    public function field(string $key): ?RadioField
    {
        return @$this->fields[$key]['field'];
    }

    /**
     * @param FormWrapper $form
     * @return static
     */
    public function setForm(FormWrapper $form)
    {
        $this->form = $form;
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
        }
        return false;
    }

    public function id(): ?string
    {
        if ($this->form()) {
            return $this->form()->id() . '--' . parent::id();
        } else {
            return parent::id();
        }
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

    public function required(): bool
    {
        return $this->required;
    }

    public function default()
    {
        foreach ($this->fields as $f) {
            if ($f['field']->default()) {
                return $f['value'];
            }
        }
        return null;
    }
    public function value(bool $useDefault = false): string|null
    {
        foreach ($this->fields as $f) {
            $f['field']->setForm($this->form());
            if ($f['field']->value($useDefault)) {
                return $f['value'];
            }
        }
        return null;
    }

    /**
     * @param bool $required
     * @param string|null $message
     * @return static
     */
    public function setRequired(bool $required, string $message = null)
    {
        $this->required = $required;
        $this->requiredMessage = $message ?? $this->requiredMessage;
        return $this;
    }

    /**
     * @param string|null $default
     * @return static
     */
    public function setDefault($default)
    {
        foreach ($this->fields as $f) {
            if ($f['value'] == $default) {
                $f['field']->setDefault(true);
            } else {
                $f['field']->setDefault(false);
            }
        }
        return $this;
    }

    /**
     * @param array|null $value
     * @return static
     */
    public function setValue($value)
    {
        foreach ($this->fields as $f) {
            if ($f['value'] == $value) {
                $f['field']->setValue(true);
            } else {
                $f['field']->setValue(false);
            }
        }
        return $this;
    }
}