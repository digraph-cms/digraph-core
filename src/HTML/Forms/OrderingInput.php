<?php

namespace DigraphCMS\HTML\Forms;

class OrderingInput extends INPUT
{
    protected $default = [];
    protected $labels = [];
    protected $allowDeletion = false;
    protected $allowAdding = false;

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'hidden',
                'value' => json_encode($this->value(true)),
                'data-labels' => json_encode($this->labels()),
                'data-allow-deletion' => $this->allowDeletion()
            ]
        );
    }

    /**
     * @suppress PHP0406
     * @return string|null
     */
    public function validationError(): ?string
    {
        if (!$this->allowAdding() && array_diff($this->value(true), $this->default())) {
            return "Adding values is not allowed";
        }
        if (!$this->allowDeletion() && array_diff($this->default(), $this->value(true))) {
            return "Deleting values is not allowed";
        }
        return parent::validationError();
    }

    public function allowAdding(): bool
    {
        return $this->allowAdding;
    }

    /**
     * Undocumented function
     *
     * @param boolean $allowAdding
     * @return static
     */
    public function setAllowAdding(bool $allowAdding)
    {
        $this->allowAdding = $allowAdding;
        return $this;
    }

    public function allowDeletion(): bool
    {
        return $this->allowDeletion;
    }

    /**
     * Undocumented function
     *
     * @param boolean $allowDeletion
     * @return static
     */
    public function setAllowDeletion(bool $allowDeletion)
    {
        $this->allowDeletion = $allowDeletion;
        return $this;
    }

    public function labels(): array
    {
        return $this->labels;
    }

    /**
     * Undocumented function
     *
     * @param array $labels
     * @return static
     */
    public function setLabels(array $labels)
    {
        $this->labels = $labels;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param string $value
     * @param string $label
     * @return static
     */
    public function addLabel(string $value, string $label)
    {
        $this->labels[$value] = $label;
        return $this;
    }

    /**
     * @return array<string,string>
     */
    public function value(bool $useDefault = false): array
    {
        $value = parent::value($useDefault);
        if (is_string($value)) {
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }
        return $value;
    }

    public function classes(): array
    {
        return array_merge(
            parent::classes(),
            [
                'ordering-input'
            ]
        );
    }
}