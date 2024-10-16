<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;

class SELECT extends Tag implements InputInterface
{
    protected $tag = 'select';
    protected $options = [];
    protected $required = false;
    protected $requiredMessage = 'This field is required';
    protected $value;
    protected $default;
    protected $validators = [];
    protected bool $disabled = false;

    /** @var FormWrapper|null */
    protected $form;

    protected static $counter = 0;

    public function __construct(array $options = null, string $null_label = null)
    {
        if ($null_label) {
            $this->setOption(null, $null_label);
        }
        if (!is_null($options)) {
            $this->setOptions($options);
        }
        $this->setID('select-' . static::$counter++);
    }

    public function children(): array
    {
        $children = [];
        foreach ($this->options as $key => $opt) {
            $children[] = sprintf(
                '<option value="%s"%s>%s</option>',
                $key,
                ($this->value(true) === $opt['value'])
                    ? ' selected="true"'
                    : '',
                $opt['label']
            );
        }
        return $children;
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

    public function attributes(): array
    {
        $attributes = array_merge(
            parent::attributes(),
            [
                'name' => $this->id(),
                'form' => $this->form() ? $this->form()->formID() : null
            ]
        );
        if ($this->disabled()) {
            $attributes['disabled'] = null;
        }
        return $attributes;
    }

    /**
     * Set options from an array of values/labels
     *
     * @param array|null $options
     * @return static
     */
    public function setOptions(array $options = null)
    {
        if ($options === null) {
            $this->options = [];
            return $this;
        }
        foreach ($options as $k => $v) {
            $this->setOption($k, $v);
        }
        return $this;
    }

    /**
     * Set an option using a value that will be returned and a label for users
     *
     * @param mixed $value
     * @param string $label
     * @return static
     */
    public function setOption($value, string $label)
    {
        if ($value === true) $key = 'true';
        elseif ($value === false) $key = 'false';
        elseif ($value === null) $key = 'null';
        elseif (is_int($value)) $key = $value;
        elseif (is_string($value) && !str_contains($value, '"')) $key = $value;
        else $key = crc32(serialize($value));
        $this->options[$key] = [
            'value' => $value,
            'label' => $label
        ];
        return $this;
    }

    public function validationError(): ?string
    {
        if ($this->required() && is_null($this->value())) {
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
     * @param string|int|null $value
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
     * @param string|int|null $value
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
        $key = $this->submittedValue();
        if (!is_null($key)) {
            return @$this->options[$key]['value'];
        } elseif ($useDefault) {
            return $this->default();
        } else {
            return null;
        }
    }

    public function addChild($child)
    {
        throw new \Exception("Can't add children to a SELECT");
    }
}
