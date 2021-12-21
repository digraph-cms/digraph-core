<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;

class CheckboxList extends DIV implements InputInterface
{
    protected static $counter = 0;
    protected $fields = [];
    protected $form;
    protected $required = false;
    protected $requiredMessage = 'This field is required';

    public function __construct(array $options = [])
    {
        $this->setID('checkbox-list-' . static::$counter++);
        $this->addClass('checkbox-list');
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
        $field = (new CheckboxField($label))
            ->setID($key);
        $this->fields[$key] = [
            'field' => $field,
            'value' => $value
        ];
    }

    public function field(string $key): ?CheckboxField
    {
        return @$this->fields[$key]['field'];
    }

    /**
     * @param FORM $form
     * @return $this
     */
    public function setForm(FORM $form)
    {
        $this->form = $form;
        return $this;
    }

    public function form(): ?FORM
    {
        return $this->form;
    }

    public function submitted(): bool
    {
        if ($this->form()) {
            return $this->form()->submitted();
        }
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
            var_dump($this->value());
            return $this->requiredMessage;
        } else {
            return null;
        }
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function default()
    {
        return array_filter(array_map(
            function ($f) {
                if ($f['field']->default()) {
                    return $f['value'];
                } else {
                    return false;
                }
            },
            $this->fields
        ));
    }

    public function value($useDefault = false)
    {
        return array_filter(array_map(
            function ($f) use ($useDefault) {
                $f['field']->setForm($this->form());
                if ($f['field']->value($useDefault)) {
                    return $f['value'];
                } else {
                    return false;
                }
            },
            $this->fields
        ));
    }

    /**
     * @param bool $required
     * @param string|null $message
     * @return $this
     */
    public function setRequired(bool $required, string $message = null)
    {
        $this->required = $required;
        $this->requiredMessage = $message ?? $this->requiredMessage;
        return $this;
    }

    /**
     * @param array|null $required
     * @return $this
     */
    public function setDefault($default)
    {
        foreach ($this->fields as $f) {
            if (in_array($f['value'], $default)) {
                $f['field']->setDefault(true);
            } else {
                $f['field']->setDefault(false);
            }
        }
    }

    /**
     * @param array|null $required
     * @return $this
     */
    public function setValue($value)
    {
        foreach ($this->fields as $f) {
            if (in_array($f['value'], $value)) {
                $f['field']->setValue(true);
            } else {
                $f['field']->setValue(false);
            }
        }
    }
}
